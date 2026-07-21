<?php
// ============================================================
// SILOSMART - ACTIVITY LOG + CAMERA SNAPSHOT API
// POST /api/activity/log.php
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
ss_session_start();

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$data = json_decode(file_get_contents('php://input'), true) ?? [];

$action      = clean($data['action'] ?? '');
$category    = clean($data['category'] ?? 'system');
$description = clean($data['description'] ?? '');
$entity_type = clean($data['entity_type'] ?? '');
$entity_id   = (int)($data['entity_id'] ?? 0);
$location    = $data['location'] ?? null;
$snapshot    = $data['snapshot'] ?? null; // base64 image from camera
$fingerprint = clean($data['fingerprint'] ?? '');

if (!$action) json_response(['error' => 'Action is required.'], 400);

$user = ss_get_current_user();
if (!$user) json_response(['error' => 'Unauthorized'], 401);

$ip  = get_client_ip();
$ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';
$dev = parse_device($ua);

// ─── Save Camera Snapshot ─────────────────────────────────────
$snapshot_path = null;
if ($snapshot && get_setting('camera_snapshot_enabled', '1') === '1') {
    if (strpos($snapshot, 'data:image') === 0) {
        $img_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $snapshot));
        if ($img_data && strlen($img_data) > 1000) { // Valid image
            $dir = SNAPSHOTS_PATH . '/' . date('Y/m/');
            if (!is_dir($dir)) mkdir($dir, 0750, true);
            $filename = 'snap_' . $user['id'] . '_' . time() . '_' . uniqid() . '.jpg';
            $filepath = $dir . $filename;

            // Compress and save
            $img = imagecreatefromstring($img_data);
            if ($img) {
                imagejpeg($img, $filepath, 75);
                imagedestroy($img);
                $snapshot_path = date('Y/m/') . $filename;
            } else {
                // Fallback: save raw
                file_put_contents($filepath, $img_data);
                $snapshot_path = date('Y/m/') . $filename;
            }
        }
    }
}

// ─── GeoIP Lookup ─────────────────────────────────────────────
$geo = ['city' => null, 'country' => null, 'isp' => null, 'is_vpn' => false];
// Use ip-api.com free tier for GeoIP
$geo_url = "http://ip-api.com/json/{$ip}?fields=status,city,country,isp,query,proxy,hosting";
$geo_resp = @file_get_contents($geo_url);
if ($geo_resp) {
    $geo_data = json_decode($geo_resp, true);
    if ($geo_data && $geo_data['status'] === 'success') {
        $geo['city']    = $geo_data['city'] ?? null;
        $geo['country'] = $geo_data['country'] ?? null;
        $geo['isp']     = $geo_data['isp'] ?? null;
        $geo['is_vpn']  = ($geo_data['proxy'] ?? false) || ($geo_data['hosting'] ?? false);
    }
}

// ─── GPS from browser ─────────────────────────────────────────
$lat = $location['lat'] ?? null;
$lng = $location['lng'] ?? null;
if ($lat) $lat = (float)$lat;
if ($lng) $lng = (float)$lng;

// ─── Risk Scoring ─────────────────────────────────────────────
$risk_score = 0;
$is_suspicious = false;

// VPN/Proxy usage
if ($geo['is_vpn']) $risk_score += 30;

// Foreign country for Kenyan org
$expected_countries = ['Kenya'];
if ($geo['country'] && !in_array($geo['country'], $expected_countries) && $user['organisation_id']) {
    $risk_score += 25;
}

// Unusual time (2am-5am Nairobi)
$hour = (int)date('H');
if ($hour >= 2 && $hour <= 5) $risk_score += 15;

// High-value actions
$high_risk_actions = ['payment_initiated', 'export_data', 'delete_silo', 'change_password', 'add_user'];
if (in_array($action, $high_risk_actions)) $risk_score += 20;

if ($risk_score >= 50) $is_suspicious = true;

// ─── Impossible travel check ──────────────────────────────────
if ($lat && $lng && $user) {
    $last = db()->prepare("
        SELECT location_lat, location_lng, logged_at 
        FROM activity_logs 
        WHERE user_id = ? AND location_lat IS NOT NULL 
        ORDER BY logged_at DESC LIMIT 1
    ");
    $last->execute([$user['id']]);
    $last_loc = $last->fetch();

    if ($last_loc && $last_loc['location_lat']) {
        $dist_km = haversine((float)$last_loc['location_lat'], (float)$last_loc['location_lng'], $lat, $lng);
        $time_diff_h = (time() - strtotime($last_loc['logged_at'])) / 3600;
        if ($time_diff_h > 0 && ($dist_km / $time_diff_h) > 900) { // 900 km/h = impossible travel
            $risk_score += 50;
            $is_suspicious = true;
        }
    }
}

// ─── Insert Log ───────────────────────────────────────────────
$stmt = db()->prepare("
    INSERT INTO activity_logs 
    (user_id, organisation_id, action, category, description, entity_type, entity_id,
     ip_address, isp, user_agent, browser, os, device_type, device_fingerprint,
     location_lat, location_lng, location_city, location_country,
     camera_snapshot, is_vpn, is_suspicious, risk_score, logged_at)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())
");
$stmt->execute([
    $user['id'], $user['organisation_id'],
    $action, $category, $description, $entity_type ?: null, $entity_id ?: null,
    $ip, $geo['isp'], substr($ua, 0, 500), $dev['browser'], $dev['os'], $dev['type'], $fingerprint ?: null,
    $lat, $lng, $geo['city'], $geo['country'],
    $snapshot_path, (int)$geo['is_vpn'], (int)$is_suspicious, $risk_score
]);

$log_id = db()->lastInsertId();

// ─── Suspicious activity alert ────────────────────────────────
if ($is_suspicious && $risk_score >= 70) {
    // Notify super admin
    $super_admins = db()->query("SELECT id FROM users WHERE role = 'super_admin' AND is_active = 1")->fetchAll();
    foreach ($super_admins as $sa) {
        create_notification($sa['id'], 'system',
            '⚠️ Suspicious Activity Detected',
            "High-risk action by {$user['first_name']} {$user['last_name']} (Risk: $risk_score/100) from $ip",
            '/admin/?panel=activity'
        );
    }
}

json_response([
    'success'    => true,
    'log_id'     => $log_id,
    'risk_score' => $risk_score,
    'suspicious' => $is_suspicious,
    'snapshot'   => $snapshot_path ? true : false,
]);

// ─── Haversine distance ───────────────────────────────────────
function haversine($lat1, $lon1, $lat2, $lon2) {
    $R = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2)*sin($dLat/2) + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLon/2)*sin($dLon/2);
    return $R * 2 * atan2(sqrt($a), sqrt(1-$a));
}