<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']) ? true : false;

if (empty($username) || empty($password)) {
    header('Location: index.php?error=invalid_credentials');
    exit;
}

try {
    $db = new Database();

    $db->query('
        SELECT id, username, email, password_hash, first_name, last_name, role, is_active
        FROM users
        WHERE username = :username
        LIMIT 1
    ');

    $db->bind(':username', $username);
    $user = $db->single();

    if (!$user) {
        header('Location: index.php?error=invalid_credentials');
        exit;
    }

    if (!$user['is_active']) {
        header('Location: index.php?error=account_disabled');
        exit;
    }

    if (!password_verify($password, $user['password_hash'])) {
        header('Location: index.php?error=invalid_credentials');
        exit;
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['login_time'] = time();

    if ($remember) {
        setcookie('username', $username, time() + (30 * 24 * 60 * 60), '/');
    }

    $db->query('
        UPDATE users
        SET last_login = NOW()
        WHERE id = :id
    ');
    $db->bind(':id', $user['id']);
    $db->execute();

    header('Location: dashboard.php');
    exit;

} catch (Exception $e) {
    error_log('Login Error: ' . $e->getMessage());
    header('Location: index.php?error=system_error');
    exit;
}
?>
