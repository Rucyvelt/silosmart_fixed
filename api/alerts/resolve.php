<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'Method not allowed']); exit; }
require_once dirname(__DIR__,2).'/includes/functions.php';
ss_session_start();
if (!is_logged_in()) { http_response_code(401); echo json_encode(['error'=>'Not authenticated']); exit; }

$data = json_decode(file_get_contents('php://input'),true) ?: $_POST;
$id   = (int)($data['id'] ?? 0);
if (!$id) { echo json_encode(['error'=>'Alert ID required']); exit; }

try {
    $user = ss_get_current_user();
    db()->prepare("UPDATE alerts SET status='resolved',resolved_by=?,resolved_at=NOW() WHERE id=?")
       ->execute([$user['id'],$id]);
    log_activity('resolve','alerts',"Resolved alert #$id",'alert',$id);
    echo json_encode(['success'=>true]);
} catch(Exception $e) {
    echo json_encode(['error'=>$e->getMessage()]);
}
