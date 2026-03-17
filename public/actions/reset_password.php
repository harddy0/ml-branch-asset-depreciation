<?php
require_once __DIR__ . '/../../src/includes/init.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/forgot_password/'); exit;
}
$username = trim($_POST['username'] ?? '');
if (empty($username)) {
    $_SESSION['error'] = 'Please enter your username.';
    header('Location: ' . BASE_URL . '/public/forgot_password/'); exit;
}
$stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(:u) LIMIT 1");
$stmt->execute([':u' => $username]);
$user = $stmt->fetch(\PDO::FETCH_ASSOC);
if (!$user) {
    $_SESSION['error'] = 'Username not found.';
    header('Location: ' . BASE_URL . '/public/forgot_password/'); exit;
}
$result = $auth->resetPassword((int)$user['id']);
if ($result['success']) {
    $_SESSION['flash_success'] = 'Password reset to default (DefaultPass1!). You will be prompted to change it on next login.';
    header('Location: ' . BASE_URL . '/public/login/');
} else {
    $_SESSION['error'] = $result['error'];
    header('Location: ' . BASE_URL . '/public/forgot_password/');
}
exit;
