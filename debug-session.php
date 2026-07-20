<?php
// TEMPORARY DEBUG FILE - DELETE AFTER TESTING
require_once __DIR__ . '/includes/functions.php';
ss_session_start();
header('Content-Type: application/json');
echo json_encode([
    'session_id'   => session_id(),
    'user_id'      => $_SESSION['user_id'] ?? null,
    'user_cache'   => $_SESSION['user_cache'] ?? null,
    'user_role'    => $_SESSION['user_role'] ?? null,
    'is_logged_in' => is_logged_in(),
    'all_session'  => $_SESSION,
]);
