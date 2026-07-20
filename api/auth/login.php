<?php
// ============================================================
// SILOSMART - AUTH API: login.php
// POST /api/auth/login.php
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_response(['error' => 'Method not allowed'], 405); }

require_once dirname(__DIR__, 2) . '/includes/functions.php';
ss_session_start();

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) $data = $_POST;

$email    = sanitize_email($data['email'] ?? '');
$password = $data['password'] ?? '';
$csrf     = $data['csrf'] ?? '';

// ─── Rate limiting ────────────────────────────────────────────
$ip = get_client_ip();
$rate_key = 'login_attempts_' . md5($ip);
$attempts = $_SESSION[$rate_key] ?? 0;
$last_attempt = $_SESSION[$rate_key . '_time'] ?? 0;

if ($attempts >= RATE_LIMIT_LOGIN && (time() - $last_attempt) < RATE_LIMIT_WINDOW) {
    $wait = RATE_LIMIT_WINDOW - (time() - $last_attempt);
    json_response(['error' => "Too many login attempts. Try again in " . ceil($wait/60) . " minutes."], 429);
}

// ─── Validation ───────────────────────────────────────────────
if (!$email || !$password) {
    json_response(['error' => 'Email and password are required.'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['error' => 'Invalid email address.'], 400);
}


// ─── DEMO MODE (no database needed) ──────────────────────────
// Remove this block when a real MySQL database is connected.
define('DEMO_MODE', true);
if (defined('DEMO_MODE') && DEMO_MODE) {
    $demo_users = [
        'admin@silosmart.io' => [
            'id' => 1, 'role' => 'super_admin',
            'first_name' => 'Super', 'last_name' => 'Admin',
            'email' => 'admin@silosmart.io', 'password' => 'password',
            'org_name' => null, 'redirect' => '/admin/'
        ],
        'james@agristore.co.ke' => [
            'id' => 2, 'role' => 'tenant_admin',
            'first_name' => 'James', 'last_name' => 'Mwangi',
            'email' => 'james@agristore.co.ke', 'password' => 'password',
            'org_name' => 'AgriStore Kenya Ltd', 'redirect' => '/dashboard.php'
        ],
        'grace@agristore.co.ke' => [
            'id' => 3, 'role' => 'operator',
            'first_name' => 'Grace', 'last_name' => 'Akinyi',
            'email' => 'grace@agristore.co.ke', 'password' => 'password',
            'org_name' => 'AgriStore Kenya Ltd', 'redirect' => '/dashboard.php'
        ],
    ];
    if (isset($demo_users[$email]) && $demo_users[$email]['password'] === $password) {
        $u = $demo_users[$email];
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['user_cache'] = [
            'id' => $u['id'], 'role' => $u['role'],
            'first_name' => $u['first_name'], 'last_name' => $u['last_name'],
            'email' => $u['email'], 'org_name' => $u['org_name'],
            'is_active' => 1, 'otp_verified' => 1,
        ];
        json_response([
            'success'  => true,
            'message'  => 'Login successful (demo)',
            'user'     => [
                'id'       => $u['id'],
                'name'     => $u['first_name'] . ' ' . $u['last_name'],
                'email'    => $u['email'],
                'role'     => $u['role'],
                'org_name' => $u['org_name'],
            ],
            'token'    => bin2hex(random_bytes(32)),
            'redirect' => $u['redirect'],
        ]);
    } else {
        json_response(['error' => 'Invalid email or password.'], 401);
    }
}
// ─── END DEMO MODE ────────────────────────────────────────────

// ─── Find User ────────────────────────────────────────────────
$stmt = db()->prepare("
    SELECT u.*, o.name as org_name, o.slug as org_slug, o.status as org_status 
    FROM users u 
    LEFT JOIN organisations o ON u.organisation_id = o.id 
    WHERE u.email = ? AND u.auth_provider = 'local'
    LIMIT 1
");
$stmt->execute([$email]);
$user = $stmt->fetch();

// ─── Account Locked? ──────────────────────────────────────────
if ($user && $user['locked_until'] && strtotime($user['locked_until']) > time()) {
    $wait = ceil((strtotime($user['locked_until']) - time()) / 60);
    log_activity('login_blocked', 'auth', "Account locked. Email: $email");
    json_response(['error' => "Account temporarily locked. Try again in $wait minutes."], 423);
}

// ─── Verify Password ──────────────────────────────────────────
if (!$user || !verify_password($password, $user['password_hash'])) {
    // Increment failed attempts
    $_SESSION[$rate_key] = $attempts + 1;
    $_SESSION[$rate_key . '_time'] = time();

    if ($user) {
        $fails = $user['failed_login_attempts'] + 1;
        if ($fails >= (int)get_setting('max_login_attempts', 5)) {
            $lock_until = date('Y-m-d H:i:s', time() + 900); // 15 min lock
            db()->prepare("UPDATE users SET failed_login_attempts = ?, locked_until = ? WHERE id = ?")
                ->execute([$fails, $lock_until, $user['id']]);
            log_activity('account_locked', 'auth', "Account locked after $fails failed attempts. Email: $email");
            json_response(['error' => 'Account locked for 15 minutes due to too many failed attempts.'], 423);
        }
        db()->prepare("UPDATE users SET failed_login_attempts = ? WHERE id = ?")->execute([$fails, $user['id']]);
    }
    log_activity('login_failed', 'auth', "Failed login attempt. Email: $email");
    json_response(['error' => 'Invalid email or password.'], 401);
}

// ─── Account Status Checks ────────────────────────────────────
if (!$user['is_active']) {
    json_response(['error' => 'Your account has been deactivated. Contact support.'], 403);
}

if (!$user['otp_verified']) {
    json_response([
        'error'   => 'Phone verification required.',
        'action'  => 'verify_otp',
        'user_id' => $user['id']
    ], 403);
}

// Organisation check (non-super-admins)
if ($user['role'] !== 'super_admin' && $user['organisation_id']) {
    if ($user['org_status'] === 'suspended') {
        json_response(['error' => 'Your organisation account is suspended. Contact your administrator.'], 403);
    }
}

// ─── Success: Create Session ──────────────────────────────────
login_user($user['id']);

// Create session record
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', time() + (int)get_setting('session_lifetime', 86400));
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$device = parse_device($ua);
$fp = md5($ua . ($_SERVER['HTTP_ACCEPT'] ?? '') . ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));

db()->prepare("INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, device_fingerprint, expires_at) VALUES (?,?,?,?,?,?)")
    ->execute([$user['id'], $token, $ip, substr($ua, 0, 500), $fp, $expires]);

// Reset failed attempts
db()->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?")->execute([$user['id']]);

// Log activity
log_activity('login_success', 'auth', "Successful login from {$device['browser']} / {$device['os']}");

// Clear rate limit
unset($_SESSION[$rate_key], $_SESSION[$rate_key . '_time']);

$redirect = '/dashboard.php';
if ($user['role'] === 'super_admin') $redirect = '/admin/';

json_response([
    'success'  => true,
    'message'  => 'Login successful',
    'user'     => [
        'id'       => $user['id'],
        'name'     => $user['first_name'] . ' ' . $user['last_name'],
        'email'    => $user['email'],
        'role'     => $user['role'],
        'org_name' => $user['org_name'],
    ],
    'token'    => $token,
    'redirect' => $redirect
]);
