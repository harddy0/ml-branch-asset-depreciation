<?php
require_once __DIR__ . '/../../src/includes/init.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $auth->login($_POST['username'] ?? '', $_POST['password'] ?? '');
    if ($result['success']) {
        $redirect = !empty($_SESSION['must_change_password'])
            ? BASE_URL . '/public/change_password/'
            : BASE_URL . '/public/dashboard/';
        header('Location: ' . $redirect);
    } else {
        $_SESSION['error'] = $result['error'];
        header('Location: ' . BASE_URL . '/public/login/');
    }
    exit;
}
header('Location: ' . BASE_URL . '/public/login/');
exit;
