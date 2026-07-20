<?php
// ============================================================
// SILOSMART - OTP API: send-otp.php
// POST /api/auth/send-otp.php
// ============================================================
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
require_once dirname(__DIR__, 2) . '/includes/functions.php';
ss_session_start();

$data  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$phone = clean($data['phone'] ?? '');
$email = sanitize_email($data['email'] ?? '');
$type  = clean($data['type'] ?? 'registration'); // registration | login | reset

if (!$phone && !$email) json_response(['error' => 'Phone or email is required.'], 400);

// Rate limit OTP requests
$rate_key = 'otp_' . md5($phone . $email);
$attempts = $_SESSION[$rate_key] ?? 0;
$last     = $_SESSION[$rate_key . '_time'] ?? 0;

if ($attempts >= RATE_LIMIT_OTP && (time() - $last) < RATE_LIMIT_WINDOW) {
    json_response(['error' => 'Too many OTP requests. Wait 15 minutes.'], 429);
}

$otp     = generate_otp(6);
$expires = time() + ((int)get_setting('otp_expiry_minutes', 5) * 60);

// Store in session for registration flow
$_SESSION['reg_otp']         = $otp;
$_SESSION['reg_otp_phone']   = $phone;
$_SESSION['reg_otp_expires'] = $expires;
$_SESSION[$rate_key]         = $attempts + 1;
$_SESSION[$rate_key . '_time'] = time();

// If user_id provided, store in DB
if (!empty($data['user_id'])) {
    $uid = (int)$data['user_id'];
    store_otp($uid, $otp);
}

// ─── Send via SMS (Africa's Talking / Twilio) ─────────────────
$sms_sent = false;
if ($phone) {
    $sms_sent = send_sms($phone, "Your SiloSmart verification code is: $otp\nExpires in 5 minutes. Do not share.");
}

// ─── Send via Email ────────────────────────────────────────────
$email_sent = false;
if ($email) {
    $email_sent = send_email(
        $email,
        'SiloSmart – Your OTP Code',
        otp_email_template($otp, (int)get_setting('otp_expiry_minutes', 5))
    );
}

json_response([
    'success'     => true,
    'message'     => 'OTP sent successfully.',
    'sms_sent'    => $sms_sent,
    'email_sent'  => $email_sent,
    'expires_in'  => 300, // seconds
    'debug_otp'   => (MPESA_ENV === 'sandbox') ? $otp : null, // Remove in production!
]);

// ─── SMS SENDER ───────────────────────────────────────────────
function send_sms($phone, $message) {
    // Africa's Talking integration (add credentials to config)
    // $at_key = get_setting('africastalking_key');
    // $at_user = get_setting('africastalking_username');
    // For demo, return true (integrate with real SMS gateway)
    error_log("SMS to $phone: $message");
    return true;
}

// ─── EMAIL SENDER ─────────────────────────────────────────────
function send_email($to, $subject, $html_body) {
    // PHPMailer / SMTP integration
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    return mail($to, $subject, $html_body, $headers);
}

function otp_email_template($otp, $expiry_mins) {
    return "<!DOCTYPE html><html><body style='font-family:sans-serif;background:#0a1628;color:#fff;padding:40px'>
    <div style='max-width:480px;margin:0 auto;background:#0d1f3c;border:1px solid rgba(212,160,23,.2);border-radius:16px;padding:2rem'>
        <h2 style='color:#D4A017;font-size:1.5rem;margin-bottom:1rem'>SiloSmart Verification</h2>
        <p>Your one-time verification code is:</p>
        <div style='background:rgba(212,160,23,.08);border:1px solid rgba(212,160,23,.3);border-radius:12px;padding:1.5rem;text-align:center;margin:1.5rem 0'>
            <span style='font-size:2.5rem;font-weight:800;letter-spacing:.5rem;color:#D4A017'>$otp</span>
        </div>
        <p style='color:#8899aa;font-size:.875rem'>This code expires in $expiry_mins minutes.<br>Do not share this code with anyone.</p>
    </div></body></html>";
}
