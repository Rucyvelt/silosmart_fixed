<?php
// ============================================================
// SILOSMART - CORE CONFIGURATION
// ============================================================

define('SS_VERSION', '2.0.0');
define('SS_NAME', 'SiloSmart');

// ─── Database ───────────────────────────────────────────────
define('DB_HOST', 'sql313.infinityfree.com');
define('DB_NAME', 'if0_41419719_silosmart_db');
define('DB_USER', 'if0_41419719');        // Change for production
define('DB_PASS', 'Gift12682109');            // Change for production
define('DB_CHARSET', 'utf8mb4');

// ─── Application URLs ────────────────────────────────────────
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', $protocol . '://' . $host);
define('ADMIN_URL', BASE_URL . '/admin');
define('FRONTEND_URL', BASE_URL);
define('API_URL', BASE_URL . '/api');

// ─── Paths ────────────────────────────────────────────────────
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('SNAPSHOTS_PATH', UPLOADS_PATH . '/snapshots');

// ─── Security ────────────────────────────────────────────────
define('JWT_SECRET', 'SS_JWT_' . md5('change_this_secret_in_production_silosmart2025'));
define('ENCRYPTION_KEY', 'SS_ENC_' . md5('change_this_encryption_key_silosmart2025'));
define('SESSION_NAME', 'SILOSMART_SESSION');
define('CSRF_TOKEN_NAME', 'ss_csrf');

// ─── M-Pesa ──────────────────────────────────────────────────
define('MPESA_ENV', 'sandbox'); // 'sandbox' or 'production'
define('MPESA_CONSUMER_KEY', '');
define('MPESA_CONSUMER_SECRET', '');
define('MPESA_SHORTCODE', '174379');
define('MPESA_PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919');
define('MPESA_CALLBACK_URL', API_URL . '/mpesa/callback.php');
define('MPESA_AUTH_URL', 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
define('MPESA_STK_URL', 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');

// ─── Social Login ─────────────────────────────────────────────
define('GOOGLE_CLIENT_ID', '');
define('GOOGLE_CLIENT_SECRET', '');
define('GOOGLE_REDIRECT', BASE_URL . '/auth/google-callback.php');

// ─── Email ───────────────────────────────────────────────────
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_FROM', 'noreply@silosmart.io');
define('SMTP_FROM_NAME', 'SiloSmart Platform');

// ─── Rate Limiting ────────────────────────────────────────────
define('RATE_LIMIT_LOGIN', 5);           // Max login attempts per window
define('RATE_LIMIT_OTP', 3);             // Max OTP requests per window
define('RATE_LIMIT_WINDOW', 900);        // 15 minutes

// ─── Timezone ─────────────────────────────────────────────────
date_default_timezone_set('Africa/Nairobi');
