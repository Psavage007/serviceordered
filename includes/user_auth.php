<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function auth_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function auth_check(string $redirect = '/login.php'): array {
    if (empty($_SESSION['user'])) {
        header('Location: ' . $redirect);
        exit;
    }
    return $_SESSION['user'];
}

function auth_login(PDO $db, string $email, string $password): bool {
    $stmt = $db->prepare('SELECT id, email, name, role, password_hash FROM users WHERE email = ?');
    $stmt->execute([trim(strtolower($email))]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        return true;
    }
    return false;
}

function auth_logout(): void {
    session_destroy();
    header('Location: /login.php');
    exit;
}

function auth_register(PDO $db, string $name, string $email, string $password): bool {
    try {
        $stmt = $db->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,?)');
        $stmt->execute([trim($name), trim(strtolower($email)), password_hash($password, PASSWORD_DEFAULT), 'user']);
        $user_id = $db->lastInsertId();
        $_SESSION['user'] = ['id' => $user_id, 'name' => trim($name), 'email' => strtolower($email), 'role' => 'user'];
        return true;
    } catch (PDOException $e) {
        return false; // duplicate email
    }
}

function user_get_business(PDO $db, int $user_id): ?array {
    $stmt = $db->prepare('
        SELECT b.* FROM businesses b
        JOIN claimed_businesses cb ON cb.business_id = b.id
        WHERE cb.user_id = ? AND cb.status = "approved"
        LIMIT 1
    ');
    $stmt->execute([$user_id]);
    return $stmt->fetch() ?: null;
}
