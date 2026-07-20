<?php
require_once __DIR__ . '/includes/functions.php';
ss_session_start();
if (is_logged_in()) { header('Location: /dashboard.php'); exit; }
$sent = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_email($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // TODO: send reset email
        $sent = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Forgot Password – SiloSmart</title>
<link href="https://https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&family=Lato:wght@300;400;700&display=swap:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{
  --gold:#D4A017;--gold2:#F0C040;--gold3:#B8860B;--gold-bright:#FFD700;
  --navy:#0A1F44;--navy2:#1A3A6B;--navy3:#0D1B3E;--navy4:#071230;
  --blue:#2E5EAA;--blue2:#1E90FF;--blue3:#4A7EC7;
  --red:#DC2626;--green:#16A34A;
  --white:#FFFFFF;--cream:#F5E6B2;--muted:#8BA3CC;
  --card:rgba(13,27,62,0.94);--border:rgba(212,160,23,0.22);
  --border2:rgba(46,94,170,0.3);
  --font-head:'Playfair Display',Georgia,serif;
  --font-body:'Lato','Trebuchet MS',sans-serif;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--font-body);background:var(--navy);color:var(--white);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem}
.card{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:2.5rem;width:100%;max-width:440px}
.brand{display:flex;align-items:center;gap:.75rem;font-family:var(--font-head);font-size:1.3rem;font-weight:800;text-decoration:none;color:var(--white);margin-bottom:2rem}
.brand-icon{width:36px;height:36px;background:linear-gradient(135deg,var(--gold),#B8860B);border-radius:9px;display:grid;place-items:center}
.brand span{color:var(--gold)}
h2{font-family:var(--font-head);font-size:1.5rem;font-weight:800;margin-bottom:.5rem}
p.sub{color:var(--muted);font-size:.9rem;margin-bottom:1.75rem}
label{font-size:.85rem;font-weight:500;color:rgba(255,255,255,.8);display:block;margin-bottom:.4rem}
input{width:100%;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:10px;padding:.75rem 1rem;color:var(--white);font-size:.9rem;outline:none;transition:border-color .2s}
input:focus{border-color:var(--gold)}
.btn{display:block;width:100%;padding:.85rem;background:linear-gradient(135deg,var(--gold),#B8860B);color:var(--navy);font-family:var(--font-head);font-weight:700;font-size:1rem;border:none;border-radius:10px;cursor:pointer;margin-top:1rem;text-align:center;text-decoration:none}
.alert{padding:.75rem 1rem;border-radius:8px;font-size:.875rem;margin-bottom:1rem}
.alert-err{background:rgba(255,71,87,.1);border:1px solid rgba(255,71,87,.3);color:#ff8a94}
.alert-ok{background:rgba(212,160,23,.1);border:1px solid rgba(212,160,23,.3);color:var(--gold)}
.back{display:block;text-align:center;color:var(--muted);font-size:.85rem;text-decoration:none;margin-top:1.25rem}
.back:hover{color:var(--gold)}
</style>
</head>
<body>
<div class="card">
  <a href="/" class="brand"><div class="brand-icon"><i class="fas fa-database" style="color:var(--navy)"></i></div>Silo<span>Smart</span></a>
  <h2>Reset Password</h2>
  <p class="sub">Enter your email and we'll send you a reset link.</p>
  <?php if ($error): ?><div class="alert alert-err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($sent): ?>
    <div class="alert alert-ok"><i class="fas fa-check-circle"></i> Reset link sent! Check your inbox.</div>
    <a href="/login.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Login</a>
  <?php else: ?>
  <form method="POST">
    <label for="email">Email Address</label>
    <input type="email" name="email" id="email" placeholder="you@example.com" required>
    <button type="submit" class="btn"><i class="fas fa-paper-plane"></i> Send Reset Link</button>
  </form>
  <?php endif; ?>
  <a href="/login.php" class="back"><i class="fas fa-arrow-left"></i> Back to Login</a>
</div>
</body>
</html>
