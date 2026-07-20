<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'Method not allowed']); exit; }
require_once dirname(__DIR__,2).'/includes/functions.php';
ss_session_start();
if (!is_logged_in()) { http_response_code(401); echo json_encode(['error'=>'Not authenticated']); exit; }

$data  = json_decode(file_get_contents('php://input'),true) ?: $_POST;
$title = trim($data['title'] ?? '');
if (!$title) { echo json_encode(['error'=>'Title is required']); exit; }

try {
    $pdo  = db();
    $user = ss_get_current_user();
    $org_id = $user['organisation_id'] ?? null;
    if (!$org_id) { echo json_encode(['error'=>'No organisation assigned']); exit; }

    $due = $data['due_date'] ?? null;
    if ($due) $due = date('Y-m-d H:i:s', strtotime($due));

    $pdo->prepare("INSERT INTO tasks (organisation_id,title,description,type,priority,status,created_by,due_date) VALUES(?,?,?,?,?,?,?,?)")
        ->execute([$org_id,$title,trim($data['description']??''),$data['type']??'other',$data['priority']??'medium','pending',$user['id'],$due]);

    $id = $pdo->lastInsertId();
    log_activity('create','tasks',"Created task: $title",'task',$id);
    echo json_encode(['success'=>true,'id'=>$id,'message'=>"Task '$title' created."]);
} catch(Exception $e) {
    echo json_encode(['error'=>$e->getMessage()]);
}
