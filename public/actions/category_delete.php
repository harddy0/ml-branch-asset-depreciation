<?php
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/AssetClassificationService.php';

if (!$auth->isAdmin()) {
    header('Location: ' . BASE_URL . '/public/dashboard/');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/category-mgt/');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id < 1) {
    $_SESSION['flash_error'] = 'Missing asset group ID for deletion.';
    header('Location: ' . BASE_URL . '/public/category-mgt/');
    exit;
}

$service = new \App\AssetClassificationService($pdo);
$result = $service->deleteAssetGroup($id);

if ($result['success']) {
    $_SESSION['flash_success'] = 'Asset group deleted successfully.';
} else {
    $_SESSION['flash_error'] = $result['error'] ?? 'Failed to delete asset group.';
}

header('Location: ' . BASE_URL . '/public/category-mgt/');
exit;
