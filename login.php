<?php
require_once 'includes/db.php';
require_once 'includes/seo_head.php';
require_once 'includes/user_auth.php';

if (auth_user()) { header('Location: /dashboard/'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = get_db();
    if (auth_login($db, $_POST['email'] ?? '', $_POST['password'] ?? '')) {
        $redirect = $_GET['redirect'] ?? '/dashboard/';
        header('Location: ' . $redirect);
        exit;
    }
    $error = 'Incorrect email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php seo_head(['title' => 'Sign In | ServiceOrdered', 'noindex' => true]); ?>
<style>
.auth-wrap{min-height:100vh;background:var(--gray-50);display:flex;flex-direction:column}
.auth-body{flex:1;display:flex;align-items:center;justify-content:center;padding:2rem 1rem}
.auth-card{background:#fff;border:1.5px solid var(--gray-200);border-radius:var(--radius-lg);padding:2.5rem;width:100%;max-width:420px;box-shadow:var(--shadow)}
.auth-logo{font-size:1.2rem;font-weight:800;color:var(--blue);text-align:center;margin-bottom:.25rem}
.auth-logo span{color:var(--teal)}
.auth-title{font-size:1.4rem;font-weight:800;color:var(--gray-900);text-align:center;margin-bottom:.35rem}
.auth-sub{text-align:center;font-size:.88rem;color:var(--gray-500);margin-bottom:2rem}
.form-group{margin-bottom:1.1rem}
.form-group label{display:block;font-size:.82rem;font-weight:600;color:var(--gray-700);margin-bottom:.4rem}
.form-group input{width:100%;padding:.7rem 1rem;border:1.5px solid var(--gray-200);border-radius:8px;font-size:.95rem;outline:none;font-family:inherit;transition:border-color .15s}
.form-group input:focus{border-color:var(--blue)}
.btn-auth{width:100%;padding:.85rem;background:var(--blue);color:#fff;border:none;border-radius:99px;font-size:1rem;font-weight:700;cursor:pointer;transition:background .15s;margin-top:.5rem}
.btn-auth:hover{background:var(--blue2)}
.auth-switch{text-align:center;font-size:.85rem;color:var(--gray-500);margin-top:1.25rem}
.auth-switch a{color:var(--blue);font-weight:600}
.auth-error{background:#fee2e2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;padding:.65rem 1rem;font-size:.85rem;margin-bottom:1.25rem}
.label-row{display:flex;justify-content:space-between;align-items:center}
.label-row a{font-size:.78rem;color:var(--gray-400)}
.label-row a:hover{color:var(--blue)}
</style>
</head>
<body class="auth-wrap">
<?php require_once 'includes/nav.php'; ?>
<div class="auth-body">
    <div class="auth-card">
        <div class="auth-logo">Service<span>Ordered</span></div>
        <h1 class="auth-title">Welcome back</h1>
        <p class="auth-sub">Sign in to manage your business profile</p>

        <?php if ($error): ?>
        <div class="auth-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="you@example.com" required autofocus>
            </div>
            <div class="form-group">
                <div class="label-row">
                    <label>Password</label>
                </div>
                <input type="password" name="password" placeholder="Your password" required>
            </div>
            <button type="submit" class="btn-auth">Sign In</button>
        </form>

        <div class="auth-switch">Don't have an account? <a href="/register.php">Create one free</a></div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>
