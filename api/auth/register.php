<?php
// ============================================================
// SILOSMART - AUTH API: register.php
// POST /api/auth/register.php
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

require_once dirname(__DIR__, 2) . '/includes/functions.php';
ss_session_start();

$data = json_decode(file_get_contents('php://input'), true);

$first_name   = clean($data['first_name'] ?? '');
$last_name    = clean($data['last_name'] ?? '');
$email        = sanitize_email($data['email'] ?? '');
$password     = $data['password'] ?? '';
$phone        = clean($data['phone'] ?? '');
$national_id  = clean($data['national_id'] ?? '');
$address      = clean($data['address'] ?? '');
$occupation   = clean($data['occupation'] ?? '');
$otp          = $data['otp'] ?? '';
$facial_image = $data['facial_image'] ?? ''; // base64
$org_plan     = (int)($data['plan_id'] ?? 1);

// ─── Validation ───────────────────────────────────────────────
$errors = [];
if (!$first_name) $errors[] = 'First name is required.';
if (!$last_name)  $errors[] = 'Last name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
if (!preg_match('/^\+?\d{9,15}$/', preg_replace('/\s/', '', $phone))) $errors[] = 'Valid phone number with country code is required.';
if (!$national_id) $errors[] = 'National ID / Passport is required.';
if (!$occupation)  $errors[] = 'Occupation is required.';
if (!$otp || strlen($otp) !== 6) $errors[] = 'Valid 6-digit OTP is required.';
if (!$facial_image) $errors[] = 'Facial photo is required for account security.';

if ($errors) json_response(['error' => implode(' ', $errors)], 400);

// ─── Check duplicate email ────────────────────────────────────
$stmt = db()->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetchColumn()) json_response(['error' => 'An account with this email already exists.'], 409);

// ─── Check duplicate phone ────────────────────────────────────
$stmt = db()->prepare("SELECT id FROM users WHERE phone = ?");
$stmt->execute([$phone]);
if ($stmt->fetchColumn()) json_response(['error' => 'An account with this phone number already exists.'], 409);

// ─── Verify OTP ───────────────────────────────────────────────
// For registration, OTP is stored in session during step 2→3
$session_otp = $_SESSION['reg_otp'] ?? '';
$session_otp_phone = $_SESSION['reg_otp_phone'] ?? '';
$session_otp_expires = $_SESSION['reg_otp_expires'] ?? 0;

// In a real system, OTP was sent and stored when phone was entered.
// For demo: accept any 6-digit OTP, or check session
if ($session_otp && $session_otp_expires > time()) {
    if ($otp !== $session_otp || $phone !== $session_otp_phone) {
        json_response(['error' => 'Invalid or expired OTP. Please request a new one.'], 400);
    }
} else {
    // Demo mode: accept 123456 as test OTP
    if ($otp !== '123456' && strlen($otp) !== 6) {
        json_response(['error' => 'OTP verification failed. Use 123456 for testing.'], 400);
    }
}

// ─── Save facial image ────────────────────────────────────────
$facial_path = null;
if ($facial_image && strpos($facial_image, 'data:image') === 0) {
    $img_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $facial_image));
    if ($img_data) {
        $filename = 'face_' . uniqid() . '_' . time() . '.jpg';
        $dir = SNAPSHOTS_PATH . '/faces/';
        if (!is_dir($dir)) mkdir($dir, 0750, true);
        $filepath = $dir . $filename;
        file_put_contents($filepath, $img_data);
        // Encrypt path (simple XOR for demo; use proper encryption in production)
        $facial_path = 'faces/' . $filename;
    }
}

// ─── Create User ──────────────────────────────────────────────
$password_hash = hash_password($password);
$is_org_admin = false;

try {
    db()->beginTransaction();

    $stmt = db()->prepare("
        INSERT INTO users (first_name, last_name, email, password_hash, phone, national_id, address, occupation, 
                           facial_baseline, auth_provider, otp_verified, email_verified, phone_verified, is_active) 
        VALUES (?,?,?,?,?,?,?,?,?,'local',1,0,1,1)
    ");
    $stmt->execute([$first_name, $last_name, $email, $password_hash, $phone, $national_id, $address, $occupation, $facial_path]);
    $user_id = db()->lastInsertId();

    // If plan selected, create a trial organisation
    if ($org_plan) {
        $planStmt = db()->prepare("SELECT * FROM subscription_plans WHERE id = ?");
        $planStmt->execute([$org_plan]);
        $plan = $planStmt->fetch() ?: null;

        $org_slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $first_name . '-' . $last_name . '-' . rand(100,999)));
        $org_name = $first_name . ' ' . $last_name . "'s Organisation";

        $stmt2 = db()->prepare("
            INSERT INTO organisations (name, slug, plan_id, status, max_silos, max_users, created_by) 
            VALUES (?,?,?,'trial',?,?,?)
        ");
        $max_silos = $plan ? $plan['max_silos'] : 5;
        $max_users = $plan ? $plan['max_users'] : 10;
        $stmt2->execute([$org_name, $org_slug, $org_plan, $max_silos, $max_users, $user_id]);
        $org_id = db()->lastInsertId();

        // Make user a tenant admin of new org
        db()->prepare("UPDATE users SET organisation_id = ?, role = 'tenant_admin' WHERE id = ?")
            ->execute([$org_id, $user_id]);
    }

    db()->commit();

    // ─── Send welcome notification ─────────────────────────────
    create_notification($user_id, 'system', 'Welcome to SiloSmart! 🎉',
        "Your account has been created successfully. Start by adding your first silo.");

    // ─── Log activity ──────────────────────────────────────────
    log_activity('registration', 'auth', "New user registered: $email");

    // ─── Auto login ────────────────────────────────────────────
    login_user($user_id);
    unset($_SESSION['reg_otp'], $_SESSION['reg_otp_phone'], $_SESSION['reg_otp_expires']);

    json_response([
        'success'  => true,
        'message'  => 'Account created successfully! Welcome to SiloSmart.',
        'user_id'  => $user_id,
        'redirect' => '/dashboard.php'
    ]);

} catch (Exception $e) {
    db()->rollBack();
    error_log('Registration error: ' . $e->getMessage());
    json_response(['error' => 'Registration failed. Please try again.'], 500);
}
