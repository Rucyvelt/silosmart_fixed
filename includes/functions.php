<?php
// ============================================================
// SILOSMART - DATABASE CONNECTION & CORE HELPERS
// ============================================================

require_once __DIR__ . '/config.php';

// ─── PDO Database Connection ─────────────────────────────────
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('DB Error: ' . $e->getMessage());
            throw $e; // Let callers handle gracefully
        }
    }

    public static function getInstance() {
        if (!self::$instance) self::$instance = new self();
        return self::$instance->pdo;
    }
}

function db() {
    static $warned = false;
    try {
        return Database::getInstance();
    } catch (\Throwable $e) {
        if (!$warned) { error_log('SiloSmart DB unavailable: ' . $e->getMessage()); $warned = true; }
        throw new RuntimeException('DB_UNAVAILABLE: ' . $e->getMessage());
    }
}

// ─── Session Handling ─────────────────────────────────────────
function ss_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.cookie_path', '/');
        session_name(SESSION_NAME);
        session_start();
    }
}

// ─── Auth Helpers ─────────────────────────────────────────────
function get_current_user_id() {
    ss_session_start();
    return $_SESSION['user_id'] ?? null;
}

function ss_get_current_user() {
    $uid = get_current_user_id();
    if (!$uid) return null;

    // Return cached user (set during login) — works even without DB
    if (isset($_SESSION['user_cache']) && !empty($_SESSION['user_cache']['role'])) {
        return $_SESSION['user_cache'];
    }

    // Try DB only if cache is missing
    try {
        $stmt = db()->prepare("SELECT u.*, o.name as org_name, o.slug as org_slug, o.status as org_status FROM users u LEFT JOIN organisations o ON u.organisation_id = o.id WHERE u.id = ? AND u.is_active = 1");
        $stmt->execute([$uid]);
        $user = $stmt->fetch();
        if ($user) $_SESSION['user_cache'] = $user;
        return $user ?: null;
    } catch (\Throwable $e) {
        // DB unavailable — build minimal user from session data
        return [
            'id'           => $uid,
            'role'         => $_SESSION['user_role'] ?? 'operator',
            'first_name'   => $_SESSION['user_first_name'] ?? 'User',
            'last_name'    => $_SESSION['user_last_name'] ?? '',
            'email'        => $_SESSION['user_email'] ?? '',
            'org_name'     => $_SESSION['user_org'] ?? 'SiloSmart',
            'plan_name'    => 'Professional',
            'is_active'    => 1,
            'otp_verified' => 1,
        ];
    }
}

function is_logged_in() { return !!get_current_user_id(); }

function require_login($redirect = '/') {
    if (!is_logged_in()) {
        header('Location: ' . $redirect . '?next=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function require_role($roles, $redirect = '/') {
    $user = ss_get_current_user();
    if (!$user) { header('Location: ' . $redirect); exit; }
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array($user['role'], $roles)) { header('Location: ' . $redirect . '?error=access_denied'); exit; }
}

function is_super_admin() {
    $u = ss_get_current_user();
    return $u && $u['role'] === 'super_admin';
}

function login_user($user_id) {
    ss_session_start();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user_id;
    unset($_SESSION['user_cache']);
    // Update last login (silently skip if DB unavailable)
    try {
        $ip = get_client_ip();
        db()->prepare("UPDATE users SET last_login_at = NOW(), last_login_ip = ?, failed_login_attempts = 0 WHERE id = ?")->execute([$ip, $user_id]);
    } catch (\Throwable $e) { /* demo mode: no DB */ }
}

function logout_user() {
    ss_session_start();
    $_SESSION = [];
    session_destroy();
}

// ─── CSRF ─────────────────────────────────────────────────────
function csrf_token() {
    ss_session_start();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify($token) {
    ss_session_start();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field() {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . csrf_token() . '">';
}

// ─── OTP ──────────────────────────────────────────────────────
function generate_otp($length = 6) {
    return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

function store_otp($user_id, $otp) {
    $expires = date('Y-m-d H:i:s', time() + (int)(get_setting('otp_expiry_minutes', 5) * 60));
    db()->prepare("UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?")->execute([$otp, $expires, $user_id]);
}

function verify_otp($user_id, $otp) {
    $stmt = db()->prepare("SELECT otp_code, otp_expires_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    if (!$row || $row['otp_code'] !== $otp) return false;
    if (strtotime($row['otp_expires_at']) < time()) return false;
    db()->prepare("UPDATE users SET otp_code = NULL, otp_expires_at = NULL, otp_verified = 1, phone_verified = 1 WHERE id = ?")->execute([$user_id]);
    return true;
}

// ─── Activity Logging ─────────────────────────────────────────
function log_activity($action, $category, $description = '', $entity_type = null, $entity_id = null, $extra = null) {
    try {
    $user = ss_get_current_user();
    $uid = $user ? $user['id'] : null;
    $org = $user ? $user['organisation_id'] : null;
    $ip = get_client_ip();
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $device = parse_device($ua);
    
    $stmt = db()->prepare("INSERT INTO activity_logs (user_id, organisation_id, action, category, description, entity_type, entity_id, ip_address, user_agent, browser, os, device_type, extra_data, logged_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
    $stmt->execute([$uid, $org, $action, $category, $description, $entity_type, $entity_id, $ip, substr($ua,0,500), $device['browser'], $device['os'], $device['type'], $extra ? json_encode($extra) : null]);
    } catch (\Throwable $e) { /* silently fail when DB is unavailable */ }
}

function parse_device($ua) {
    $browser = 'Unknown'; $os = 'Unknown'; $type = 'desktop';
    if (preg_match('/Chrome\/[\d.]+/', $ua)) $browser = 'Chrome';
    elseif (preg_match('/Firefox\/[\d.]+/', $ua)) $browser = 'Firefox';
    elseif (preg_match('/Safari\/[\d.]+/', $ua) && !strpos($ua,'Chrome')) $browser = 'Safari';
    elseif (preg_match('/Edge\/[\d.]+/', $ua)) $browser = 'Edge';
    if (preg_match('/Windows/', $ua)) $os = 'Windows';
    elseif (preg_match('/Mac OS X/', $ua)) $os = 'macOS';
    elseif (preg_match('/Linux/', $ua)) $os = 'Linux';
    elseif (preg_match('/Android/', $ua)) { $os = 'Android'; $type = 'mobile'; }
    elseif (preg_match('/iPhone|iPad/', $ua)) { $os = 'iOS'; $type = strpos($ua,'iPad') !== false ? 'tablet' : 'mobile'; }
    return ['browser' => $browser, 'os' => $os, 'type' => $type];
}

function get_client_ip() {
    $keys = ['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_CLIENT_IP','REMOTE_ADDR'];
    foreach ($keys as $k) {
        if (!empty($_SERVER[$k])) {
            $ip = explode(',', $_SERVER[$k])[0];
            return trim($ip);
        }
    }
    return '0.0.0.0';
}

// ─── Notifications ────────────────────────────────────────────
function create_notification($user_id, $type, $title, $message, $link = null, $org_id = null) {
    db()->prepare("INSERT INTO notifications (user_id, organisation_id, type, title, message, link) VALUES (?,?,?,?,?,?)")->execute([$user_id, $org_id, $type, $title, $message, $link]);
}

function get_unread_count($user_id) {
    try {
        $stmt = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    } catch (\Throwable $e) { return 0; }
}

// ─── System Settings ──────────────────────────────────────────
function get_setting($key, $default = null) {
    static $cache = [];
    if (!isset($cache[$key])) {
        try {
            $stmt = db()->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            $cache[$key] = $val !== false ? $val : $default;
        } catch (\Throwable $e) {
            $cache[$key] = $default;
        }
    }
    return $cache[$key];
}

function set_setting($key, $value, $uid = null) {
    db()->prepare("INSERT INTO system_settings (setting_key, setting_value, updated_by) VALUES (?,?,?) ON DUPLICATE KEY UPDATE setting_value = ?, updated_by = ?")->execute([$key, $value, $uid, $value, $uid]);
}

// ─── Password ─────────────────────────────────────────────────
function hash_password($pass) { return password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]); }
function verify_password($pass, $hash) { return password_verify($pass, $hash); }

// ─── JSON Response ────────────────────────────────────────────
function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ─── Sanitize ─────────────────────────────────────────────────
function clean($str) { return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8'); }
function sanitize_email($email) { return filter_var(trim($email), FILTER_SANITIZE_EMAIL); }

// ─── Time Ago ─────────────────────────────────────────────────
function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return $diff . 's ago';
    if ($diff < 3600) return round($diff/60) . 'm ago';
    if ($diff < 86400) return round($diff/3600) . 'h ago';
    if ($diff < 604800) return round($diff/86400) . 'd ago';
    return date('M d, Y', strtotime($datetime));
}

// ─── Pagination ───────────────────────────────────────────────
function paginate($total, $per_page, $current) {
    return [
        'total' => $total,
        'per_page' => $per_page,
        'current' => $current,
        'last' => ceil($total / $per_page),
        'from' => ($current - 1) * $per_page,
    ];
}
