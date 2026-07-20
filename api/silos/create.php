<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'Method not allowed']); exit; }
require_once dirname(__DIR__,2).'/includes/functions.php';
ss_session_start();
if (!is_logged_in()) { http_response_code(401); echo json_encode(['error'=>'Not authenticated']); exit; }

$data = json_decode(file_get_contents('php://input'),true) ?: $_POST;
$name     = trim($data['name'] ?? '');
$code     = trim($data['code'] ?? '');
$site     = trim($data['site_name'] ?? '');
$commodity= trim($data['commodity_type'] ?? 'grain_maize');
$capacity = (float)($data['capacity_tonnes'] ?? 0);
$lat      = $data['latitude'] !== '' ? (float)$data['latitude'] : null;
$lng      = $data['longitude'] !== '' ? (float)$data['longitude'] : null;

if (!$name || !$code) {
    echo json_encode(['error' => 'Silo name and code are required.']); exit;
}

try {
    $pdo = db();
    $user = ss_get_current_user();
    $org_id = $user['organisation_id'] ?? null;

    if (!$org_id) {
        echo json_encode(['error' => 'No organisation assigned to your account.']); exit;
    }

    // Check code uniqueness within org
    $exists = $pdo->prepare("SELECT id FROM silos WHERE code=? AND organisation_id=?");
    $exists->execute([$code, $org_id]);
    if ($exists->fetchColumn()) {
        echo json_encode(['error' => "Silo code '$code' already exists in your organisation."]); exit;
    }

    // Check plan limit
    $max = $pdo->query("SELECT sp.max_silos FROM organisations o LEFT JOIN subscription_plans sp ON o.plan_id=sp.id WHERE o.id=$org_id")->fetchColumn();
    $current = $pdo->query("SELECT COUNT(*) FROM silos WHERE organisation_id=$org_id")->fetchColumn();
    if ($max && $current >= $max) {
        echo json_encode(['error' => "Silo limit reached ($max silos). Upgrade your plan to add more."]); exit;
    }

    $pdo->prepare("INSERT INTO silos (organisation_id, name, code, site_name, commodity_type, capacity_tonnes, latitude, longitude, status, created_by)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)")
        ->execute([$org_id, $name, $code, $site, $commodity, $capacity ?: null, $lat, $lng, $user['id']]);

    $new_id = $pdo->lastInsertId();

    // Log activity
    log_activity('create', 'silos', "Created silo: $name ($code)", 'silo', $new_id);

    echo json_encode([
        'success' => true,
        'id'      => $new_id,
        'message' => "Silo '$name' created successfully.",
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
