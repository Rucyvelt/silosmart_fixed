<?php
// ============================================================
// SILOSMART - SENSOR DATA API
// GET  /api/sensors/readings.php  - get latest readings
// POST /api/sensors/ingest.php    - push new readings
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

require_once dirname(__DIR__, 2) . '/includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];

// ─── INGEST (POST) ────────────────────────────────────────────
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) json_response(['error' => 'Invalid JSON payload'], 400);

    // API Key auth for IoT devices
    $api_key = $_SERVER['HTTP_X_API_KEY'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
    $api_key = str_replace('Bearer ', '', $api_key);

    // Validate API key (in production: check against device_api_keys table)
    // For demo: allow requests with any key or no key in sandbox

    $readings = $data['readings'] ?? [$data]; // Support batch or single
    $inserted = 0;
    $alerts_triggered = [];

    foreach ($readings as $r) {
        $sensor_id = (int)($r['sensor_id'] ?? 0);
        $device_id = clean($r['device_id'] ?? '');
        $value     = (float)($r['value'] ?? 0);
        $quality   = in_array($r['quality'] ?? '', ['good','uncertain','bad']) ? $r['quality'] : 'good';
        $source    = in_array($r['source'] ?? '', ['automatic','manual','simulated']) ? $r['source'] : 'automatic';
        $ts        = $r['timestamp'] ?? date('Y-m-d H:i:s');

        // Find sensor by ID or device_id
        if ($sensor_id) {
            $sensor = db()->prepare("SELECT * FROM sensors WHERE id = ? AND is_active = 1");
            $sensor->execute([$sensor_id]);
            $sensor = $sensor->fetch();
        } elseif ($device_id) {
            $sensor = db()->prepare("SELECT * FROM sensors WHERE device_id = ? AND is_active = 1");
            $sensor->execute([$device_id]);
            $sensor = $sensor->fetch();
        } else {
            continue;
        }

        if (!$sensor) continue;

        // Apply calibration offset
        $value += (float)$sensor['calibration_offset'];

        // Insert reading
        db()->prepare("
            INSERT INTO sensor_readings (sensor_id, silo_id, organisation_id, value, raw_value, quality, source, recorded_at)
            VALUES (?,?,?,?,?,?,?,?)
        ")->execute([$sensor['id'], $sensor['silo_id'], $sensor['organisation_id'], $value, (float)($r['value'] ?? 0), $quality, $source, $ts]);

        // Update sensor last reading
        db()->prepare("UPDATE sensors SET last_reading = ?, last_reading_at = ? WHERE id = ?")
            ->execute([$value, $ts, $sensor['id']]);

        $inserted++;

        // ─── Alert checking ───────────────────────────────────
        check_sensor_alerts($sensor, $value, $alerts_triggered);
    }

    json_response([
        'success'          => true,
        'readings_stored'  => $inserted,
        'alerts_triggered' => count($alerts_triggered),
    ]);
}

// ─── GET READINGS ─────────────────────────────────────────────
if ($method === 'GET') {
    require_login('/login.php');
    $user = ss_get_current_user();

    $silo_id    = (int)($_GET['silo_id'] ?? 0);
    $sensor_id  = (int)($_GET['sensor_id'] ?? 0);
    $hours      = min((int)($_GET['hours'] ?? 24), 8760); // Max 1 year
    $org_id     = $user['organisation_id'];

    $where = ["sr.organisation_id = ?", "sr.recorded_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)"];
    $params = [$org_id, $hours];

    if ($silo_id)   { $where[] = "sr.silo_id = ?";   $params[] = $silo_id; }
    if ($sensor_id) { $where[] = "sr.sensor_id = ?"; $params[] = $sensor_id; }

    $sql = "
        SELECT sr.id, sr.sensor_id, sr.silo_id, sr.value, sr.quality, sr.recorded_at,
               s.name as sensor_name, s.sensor_type, s.unit,
               si.name as silo_name, si.code as silo_code
        FROM sensor_readings sr
        JOIN sensors s ON sr.sensor_id = s.id
        JOIN silos si ON sr.silo_id = si.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY sr.recorded_at DESC
        LIMIT 5000
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $readings = $stmt->fetchAll();

    // Group by sensor for chart data
    $grouped = [];
    foreach ($readings as $r) {
        $key = $r['sensor_id'];
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'sensor_id'   => $r['sensor_id'],
                'sensor_name' => $r['sensor_name'],
                'sensor_type' => $r['sensor_type'],
                'unit'        => $r['unit'],
                'silo_name'   => $r['silo_name'],
                'silo_code'   => $r['silo_code'],
                'data'        => []
            ];
        }
        $grouped[$key]['data'][] = [
            'value'  => (float)$r['value'],
            'time'   => $r['recorded_at'],
            'quality'=> $r['quality']
        ];
    }

    json_response([
        'success'  => true,
        'count'    => count($readings),
        'sensors'  => array_values($grouped),
    ]);
}

// ─── ALERT CHECKER ────────────────────────────────────────────
function check_sensor_alerts($sensor, $value, &$triggered) {
    $checks = [
        ['field' => 'critical_high', 'dir' => '>', 'severity' => 'critical'],
        ['field' => 'critical_low',  'dir' => '<', 'severity' => 'critical'],
        ['field' => 'alert_high',    'dir' => '>', 'severity' => 'warning'],
        ['field' => 'alert_low',     'dir' => '<', 'severity' => 'warning'],
    ];

    foreach ($checks as $c) {
        $threshold = $sensor[$c['field']];
        if ($threshold === null) continue;

        $breached = $c['dir'] === '>' ? $value > (float)$threshold : $value < (float)$threshold;
        if (!$breached) continue;

        // Check if this alert already exists and is active
        $exists = db()->prepare("
            SELECT id FROM alerts 
            WHERE silo_id = ? AND sensor_id = ? AND severity = ? AND status = 'active'
            AND triggered_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $exists->execute([$sensor['silo_id'], $sensor['id'], $c['severity']]);
        if ($exists->fetchColumn()) continue;

        $dir_label = $c['dir'] === '>' ? 'exceeds' : 'is below';
        $title = ucfirst($c['severity']) . ": {$sensor['name']} {$dir_label} {$c['field']}";
        $message = "{$sensor['name']} reading {$value}{$sensor['unit']} {$dir_label} {$c['field']} ({$threshold}{$sensor['unit']})";

        db()->prepare("
            INSERT INTO alerts (organisation_id, silo_id, sensor_id, type, severity, title, message, value, threshold, triggered_at)
            VALUES (?,?,?,'threshold',?,?,?,?,?,NOW())
        ")->execute([$sensor['organisation_id'], $sensor['silo_id'], $sensor['id'], $c['severity'], $title, $message, $value, $threshold]);

        // Notify all operators in org
        $operators = db()->prepare("SELECT id FROM users WHERE organisation_id = ? AND role IN ('tenant_admin','operator') AND is_active = 1");
        $operators->execute([$sensor['organisation_id']]);
        foreach ($operators->fetchAll() as $op) {
            create_notification($op['id'], 'alert', $title, $message, '/alerts.php', $sensor['organisation_id']);
        }

        $triggered[] = ['sensor' => $sensor['id'], 'severity' => $c['severity']];
        break; // Only one alert type per sensor per check
    }
}
