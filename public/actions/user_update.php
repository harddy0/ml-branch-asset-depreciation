<?php
require_once __DIR__ . '/../../src/includes/init.php';

if (!$auth->isAdmin()) {
    header('Location: ' . BASE_URL . '/public/dashboard/'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/user-mgt/'); exit;
}

$id   = (int)($_POST['id']               ?? 0);
$fn   = trim($_POST['first_name']        ?? '');
$mn   = trim($_POST['middle_name']       ?? '');
$ln   = trim($_POST['last_name']         ?? '');
$type = in_array($_POST['user_type'] ?? '', ['ADMIN', 'USER']) ? $_POST['user_type'] : 'USER';

if (!$id || empty($fn) || empty($ln)) {
    $_SESSION['flash_error'] = 'All required fields must be filled.';
    header('Location: ' . BASE_URL . '/public/user-mgt/'); exit;
}

$result = $auth->updateUser($id, $fn, $mn, $ln, $type);

if ($result['success']) {
    $_SESSION['flash_success'] = "User updated. New username: {$result['username']}";
} else {
    $_SESSION['flash_error'] = $result['error'] ?? 'Failed to update user.';
}

header('Location: ' . BASE_URL . '/public/user-mgt/');
exit;