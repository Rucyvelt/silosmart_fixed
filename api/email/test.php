<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'Method not allowed']); exit; }
require_once dirname(__DIR__,2).'/includes/functions.php';
ss_session_start();
if (!is_logged_in()) { http_response_code(401); echo json_encode(['error'=>'Not authenticated']); exit; }

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$to = filter_var($data['to'] ?? '', FILTER_VALIDATE_EMAIL);
if (!$to) { echo json_encode(['error'=>'Valid email address required.']); exit; }

try {
    $pdo = db();
    $smtp_host = get_setting('smtp_host','');
    $smtp_port = (int)get_setting('smtp_port', 587);
    $smtp_user = get_setting('smtp_username','');
    $smtp_pass = get_setting('smtp_password','');
    $from_name = get_setting('smtp_from_name','SiloSmart');
    $from_email= get_setting('smtp_from_email','noreply@silosmart.io');

    if (!$smtp_host || !$smtp_user) {
        echo json_encode(['error'=>'SMTP not configured. Fill in SMTP settings first.']); exit;
    }

    // Use PHP mail() as fallback if no SMTP library
    $subject = 'SiloSmart — Test Email';
    $body = "This is a test email from SiloSmart.\n\nIf you received this, your email settings are working correctly.\n\nSent at: ".date('Y-m-d H:i:s T');
    $headers = "From: $from_name <$from_email>\r\nReply-To: $from_email\r\nContent-Type: text/plain; charset=UTF-8";

    if (@mail($to, $subject, $body, $headers)) {
        echo json_encode(['success'=>true,'message'=>'Test email sent to '.$to]);
    } else {
        echo json_encode(['error'=>'mail() failed. Use a proper SMTP library like PHPMailer for InfinityFree.']);
    }
} catch (Exception $e) {
    echo json_encode(['error'=>'DB not connected — cannot read SMTP settings.']);
}
