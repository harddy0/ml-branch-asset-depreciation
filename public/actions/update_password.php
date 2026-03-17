<?php
require_once __DIR__ . '/../../src/includes/init.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/change_password/'); exit;
}
$new     = $_POST['new_password']     ?? '';
$confirm = $_POST['confirm_password'] ?? '';
if (strlen($new) < 8) {
    $_SESSION['cp_error'] = 'Password must be at least 8 characters.';
    header('Location: ' . BASE_URL . '/public/change_password/'); exit;
}
if ($new !== $confirm) {
    $_SESSION['cp_error'] = 'Passwords do not match.';
    header('Location: ' . BASE_URL . '/public/change_password/'); exit;
}
$auth->changeUserPassword((int)$_SESSION['user_id'], $new);
unset($_SESSION['must_change_password']);
header('Location: ' . BASE_URL . '/public/dashboard/');
exit;
