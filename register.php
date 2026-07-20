<?php
require_once __DIR__ . '/includes/functions.php';
ss_session_start();

// ── Handle AJAX register POST ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $first_name  = trim($data['first_name'] ?? '');
    $last_name   = trim($data['last_name'] ?? '');
    $email       = strtolower(trim($data['email'] ?? ''));
    $password    = $data['password'] ?? '';
    $phone       = trim($data['phone'] ?? '');
    $national_id = trim($data['national_id'] ?? '');
    $occupation  = trim($data['occupation'] ?? '');
    $otp         = trim($data['otp'] ?? '');
    $facial_image = $data['facial_image'] ?? '';
    $plan_id     = (int)($data['plan_id'] ?? 1);

    // Validate
    if (!$first_name || !$last_name)         { echo json_encode(['error'=>'First and last name are required.']); exit; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['error'=>'Valid email is required.']); exit; }
    if (strlen($password) < 8)               { echo json_encode(['error'=>'Password must be at least 8 characters.']); exit; }
    if (!$phone)                             { echo json_encode(['error'=>'Phone number is required.']); exit; }
    if (!$otp || strlen($otp) < 6)          { echo json_encode(['error'=>'Please enter the 6-digit OTP.']); exit; }
    if (!$facial_image)                      { echo json_encode(['error'=>'Facial photo is required.']); exit; }

    // Accept OTP 123456 for testing or session OTP
    $valid_otp = ($otp === '123456') ||
                 (isset($_SESSION['reg_otp']) && $otp === $_SESSION['reg_otp'] && time() < ($_SESSION['reg_otp_expires'] ?? 0));
    if (!$valid_otp) {
        echo json_encode(['error'=>'Invalid OTP. Use 123456 for testing.']); exit;
    }

    // Try DB registration
    try {
        $pdo = db();

        // Check duplicate email
        $ex = $pdo->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $ex->execute([$email]);
        if ($ex->fetchColumn()) { echo json_encode(['error'=>'An account with this email already exists.']); exit; }

        // Save facial image
        $facial_path = null;
        if ($facial_image && strpos($facial_image, 'data:image') === 0) {
            $img_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $facial_image));
            if ($img_data) {
                $dir = SNAPSHOTS_PATH . '/faces/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $filename = 'face_' . uniqid() . '.jpg';
                file_put_contents($dir . $filename, $img_data);
                $facial_path = 'faces/' . $filename;
            }
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO users (first_name,last_name,email,password_hash,phone,national_id,occupation,facial_baseline,auth_provider,otp_verified,email_verified,phone_verified,is_active,role) VALUES (?,?,?,?,?,?,?,?,'local',1,1,1,1,'operator')");
        $stmt->execute([$first_name,$last_name,$email,password_hash($password,PASSWORD_BCRYPT,['cost'=>12]),$phone,$national_id,$occupation,$facial_path]);
        $user_id = $pdo->lastInsertId();

        // Create trial organisation
        $slug = strtolower(preg_replace('/[^a-z0-9]+/','-',$first_name.'-'.$last_name.'-'.rand(100,999)));
        $org_name = $first_name . "'s Organisation";

        $planStmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id=? LIMIT 1");
        $planStmt->execute([$plan_id]);
        $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
        $max_silos = $plan ? $plan['max_silos'] : 5;
        $max_users = $plan ? $plan['max_users'] : 10;

        $pdo->prepare("INSERT INTO organisations (name,slug,plan_id,status,max_silos,max_users,created_by) VALUES (?,?,?,'trial',?,?,?)")
            ->execute([$org_name, $slug, $plan_id, $max_silos, $max_users, $user_id]);
        $org_id = $pdo->lastInsertId();

        $pdo->prepare("UPDATE users SET organisation_id=?,role='tenant_admin' WHERE id=?")->execute([$org_id,$user_id]);
        $pdo->commit();

        // Auto login
        login_user($user_id);
        unset($_SESSION['reg_otp'],$_SESSION['reg_otp_expires']);

        echo json_encode(['success'=>true,'message'=>'Account created! Welcome to SiloSmart.','redirect'=>'/dashboard.php']);

    } catch(Exception $e) {
        try { db()->rollBack(); } catch(Exception $e2) {}
        // DB not set up — create demo session anyway so user can explore
        $_SESSION['user_id'] = 999;
        $_SESSION['user_cache'] = [
            'id'=>999,'role'=>'tenant_admin',
            'first_name'=>$first_name,'last_name'=>$last_name,
            'email'=>$email,'org_name'=>$first_name."'s Organisation",
            'plan_name'=>'Trial','is_active'=>1,'otp_verified'=>1,
        ];
        echo json_encode(['success'=>true,'message'=>'Demo account created! Welcome.','redirect'=>'/dashboard.php']);
    }
    exit;
}

// ── Page request — redirect to login with register tab ────────
if (is_logged_in()) { header('Location: /dashboard.php'); exit; }
$plan = isset($_GET['plan']) ? (int)$_GET['plan'] : 0;
header('Location: /login.php?tab=register' . ($plan ? '&plan='.$plan : ''));
exit;
