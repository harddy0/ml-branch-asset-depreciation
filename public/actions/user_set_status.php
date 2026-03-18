<?php
require_once __DIR__ . '/../../src/includes/init.php';

if (!$auth->isAdmin()) {
    header('Location: ' . BASE_URL . '/public/dashboard/'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/user-mgt/'); exit;
}

$id     = (int)($_POST['id']     ?? 0);
$status = strtoupper(trim($_POST['status'] ?? ''));

if (!$id || !in_array($status, ['ACTIVE', 'RESTRICTED'])) {
    $_SESSION['flash_error'] = 'Invalid request.';
    header('Location: ' . BASE_URL . '/public/user-mgt/'); exit;
}

$result = $auth->setUserStatus($id, $status, (int)$_SESSION['user_id']);

if ($result['success']) {
    $_SESSION['flash_success'] = $status === 'RESTRICTED'
        ? 'User has been restricted and can no longer log in.'
        : 'User has been activated and can now log in.';
} else {
    $_SESSION['flash_error'] = $result['error'] ?? 'Status update failed.';
}

header('Location: ' . BASE_URL . '/public/user-mgt/');
exit;