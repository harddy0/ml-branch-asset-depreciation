<?php
require_once __DIR__ . '/../../src/includes/init.php';

if (!$auth->isAdmin()) {
    header('Location: ' . BASE_URL . '/public/dashboard/'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/user-mgt/'); exit;
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    $_SESSION['flash_error'] = 'Invalid user.';
    header('Location: ' . BASE_URL . '/public/user-mgt/'); exit;
}

$result = $auth->resetPassword($id);

if ($result['success']) {
    $_SESSION['flash_success'] = 'Password reset to Mlinc1234@. User will be required to change it on next login.';
} else {
    $_SESSION['flash_error'] = $result['error'] ?? 'Password reset failed.';
}

header('Location: ' . BASE_URL . '/public/user-mgt/');
exit;