<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function admin_check() {
    if (empty($_SESSION['admin_id'])) {
        header('Location: /admin/login.php');
        exit;
    }
}

function admin_login(PDO $db, string $email, string $password): bool {
    $stmt = $db->prepare('SELECT id, password_hash, name, role FROM users WHERE email = ? AND role = "admin"');
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['admin_id']   = $user['id'];
        $_SESSION['admin_name'] = $user['name'];
        return true;
    }
    return false;
}

function admin_logout() {
    session_destroy();
    header('Location: /admin/login.php');
    exit;
}
