<?php
require_once '../includes/db.php';
require_once 'auth.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: /admin/');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = get_db();
    if (admin_login($db, $_POST['email'] ?? '', $_POST['password'] ?? '')) {
        header('Location: /admin/');
        exit;
    }
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | ServiceOrdered</title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body{background:var(--gray-900);display:flex;align-items:center;justify-content:center;min-height:100vh}
        .login-card{background:#fff;border-radius:16px;padding:2.5rem;width:100%;max-width:400px;box-shadow:0 24px 64px rgba(0,0,0,.4)}
        .login-logo{font-size:1.3rem;font-weight:800;color:var(--blue);text-align:center;margin-bottom:.25rem}
        .login-logo span{color:var(--teal)}
        .login-sub{text-align:center;font-size:.82rem;color:var(--gray-400);margin-bottom:2rem}
        .form-group{margin-bottom:1.25rem}
        label{display:block;font-size:.82rem;font-weight:600;color:var(--gray-700);margin-bottom:.4rem}
        input[type=email],input[type=password]{width:100%;padding:.75rem 1rem;border:1.5px solid var(--gray-200);border-radius:8px;font-size:.95rem;outline:none;transition:border-color .15s}
        input:focus{border-color:var(--blue)}
        .btn-login{width:100%;padding:.85rem;background:var(--blue);color:#fff;border:none;border-radius:8px;font-size:.95rem;font-weight:700;cursor:pointer;transition:background .15s}
        .btn-login:hover{background:var(--blue2)}
        .error{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:6px;padding:.65rem 1rem;font-size:.85rem;margin-bottom:1.25rem}
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-logo">Service<span>Ordered</span></div>
    <div class="login-sub">Admin Panel</div>
    <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn-login">Sign In</button>
    </form>
</div>
</body>
</html>
