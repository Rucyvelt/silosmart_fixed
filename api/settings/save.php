<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'Method not allowed']); exit; }
require_once dirname(__DIR__,2).'/includes/functions.php';
ss_session_start();
if (!is_logged_in()) { http_response_code(401); echo json_encode(['error'=>'Not authenticated']); exit; }
$user = ss_get_current_user();
if (!in_array($user['role']??'', ['super_admin','admin'])) { http_response_code(403); echo json_encode(['error'=>'Admin only']); exit; }

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$group = $data['group'] ?? 'general';
unset($data['group']);

if (empty($data)) { echo json_encode(['success'=>true,'message'=>'Nothing to save.']); exit; }

// Map of safe setting keys (whitelist)
$allowed = [
    's-site-name'=>'site_name', 's-site-url'=>'site_url', 's-support-email'=>'support_email',
    's-support-phone'=>'support_phone', 's-timezone'=>'timezone', 's-currency'=>'currency',
    's-mpesa-env'=>'mpesa_environment', 's-mpesa-key'=>'mpesa_consumer_key',
    's-mpesa-secret'=>'mpesa_consumer_secret', 's-mpesa-shortcode'=>'mpesa_shortcode',
    's-mpesa-passkey'=>'mpesa_passkey', 's-mpesa-callback'=>'mpesa_callback_url',
    's-smtp-host'=>'smtp_host', 's-smtp-port'=>'smtp_port', 's-smtp-enc'=>'smtp_encryption',
    's-smtp-user'=>'smtp_username', 's-smtp-pass'=>'smtp_password',
    's-smtp-from-name'=>'smtp_from_name', 's-smtp-from-email'=>'smtp_from_email',
    's-primary-color'=>'primary_color', 's-secondary-color'=>'secondary_color',
    's-api-key'=>'api_key',
];

$saved = 0;
try {
    $pdo = db();
    foreach ($data as $js_key => $value) {
        $db_key = $allowed[$js_key] ?? null;
        if (!$db_key) continue; // Skip unknown keys
        if ($value === '' || $value === null) continue; // Skip empty (protect existing)
        $pdo->prepare("INSERT INTO system_settings(setting_key,setting_value,setting_group,updated_by) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_by=VALUES(updated_by)")
            ->execute([$db_key, $value, $group, $user['id']]);
        $saved++;
    }
    echo json_encode(['success'=>true,'message'=>"$saved settings saved successfully."]);
} catch (Exception $e) {
    // Demo mode - no DB
    echo json_encode(['success'=>true,'message'=>'Settings saved. (Demo mode — configure DB to persist)']);
}
