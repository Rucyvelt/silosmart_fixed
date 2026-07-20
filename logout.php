<?php
require_once __DIR__ . '/includes/functions.php';
ss_session_start();
if (is_logged_in()) {
    log_activity('logout', 'auth', 'User logged out');
    logout_user();
}
header('Location: /login.php');
exit;
