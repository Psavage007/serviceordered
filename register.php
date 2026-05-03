<?php
require_once 'includes/db.php';
require_once 'includes/seo_head.php';
require_once 'includes/user_auth.php';

if (auth_user()) { header('Location: /dashboard/'); exit; }

$error = '';
$vals  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');
    $vals     = compact('name', 'email');

    if (!$name || !$email || !$password) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db = get_db();
        if (auth_register($db, $name, $email, $password)) {
            header('Location: /dashboard/');
            exit;
        }
        $error = 'An account with that email already exists. <a href="/login.php">Sign in instead</a>.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php seo_head(['title' => 'Create Account | ServiceOrdered', 'noindex' => true]); ?>
<style>
.auth-wrap{min-height:100vh;background:var(--gray-50);display:flex;flex-direction:column}
.auth-body{flex:1;display:flex;align-items:center;justify-content:center;padding:2rem 1rem}
.auth-card{background:#fff;border:1.5px solid var(--gray-200);border-radius:var(--radius-lg);padding:2.5rem;width:100%;max-width:460px;box-shadow:var(--shadow)}
.auth-logo{font-size:1.2rem;font-weight:800;color:var(--blue);text-align:center;margin-bottom:.25rem}
.auth-logo span{color:var(--teal)}
.auth-title{font-size:1.4rem;font-weight:800;color:var(--gray-900);text-align:center;margin-bottom:.35rem}
.auth-sub{text-align:center;font-size:.88rem;color:var(--gray-500);margin-bottom:2rem}
.form-group{margin-bottom:1.1rem}
.form-group label{display:block;font-size:.82rem;font-weight:600;color:var(--gray-700);margin-bottom:.4rem}
.form-group input{width:100%;padding:.7rem 1rem;border:1.5px solid var(--gray-200);border-radius:8px;font-size:.95rem;outline:none;font-family:inherit;transition:border-color .15s}
.form-group input:focus{border-color:var(--blue)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
.btn-auth{width:100%;padding:.85rem;background:var(--blue);color:#fff;border:none;border-radius:99px;font-size:1rem;font-weight:700;cursor:pointer;transition:background .15s;margin-top:.5rem}
.btn-auth:hover{background:var(--blue2)}
.auth-switch{text-align:center;font-size:.85rem;color:var(--gray-500);margin-top:1.25rem}
.auth-switch a{color:var(--blue);font-weight:600}
.auth-error{background:#fee2e2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;padding:.65rem 1rem;font-size:.85rem;margin-bottom:1.25rem}
.auth-divider{display:flex;align-items:center;gap:.75rem;margin:1.25rem 0;color:var(--gray-400);font-size:.8rem}
.auth-divider::before,.auth-divider::after{content:'';flex:1;height:1px;background:var(--gray-200)}
</style>
</head>
<body class="auth-wrap">
<?php require_once 'includes/nav.php'; ?>
<div class="auth-body">
    <div class="auth-card">
        <div class="auth-logo">Service<span>Ordered</span></div>
        <h1 class="auth-title">Create your account</h1>
        <p class="auth-sub">Join thousands of contractors growing their business</p>

        <?php if ($error): ?>
        <div class="auth-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($vals['name'] ?? '') ?>" placeholder="John Smith" required autofocus>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?= htmlspecialchars($vals['email'] ?? '') ?>" placeholder="you@example.com" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Min. 8 characters" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm" placeholder="Repeat password" required>
                </div>
            </div>
            <button type="submit" class="btn-auth">Create Account</button>
        </form>

        <div class="auth-switch">Already have an account? <a href="/login.php">Sign in</a></div>

        <div class="auth-divider">For contractors</div>
        <p style="font-size:.78rem;color:var(--gray-400);text-align:center">After registering you can claim your existing listing or create a new business profile.</p>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>
