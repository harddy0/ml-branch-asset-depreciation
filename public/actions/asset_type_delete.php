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

$assetCode = strtoupper(trim($_POST['asset_code'] ?? ''));

if ($assetCode === '') {
    $_SESSION['flash_error'] = 'Missing asset type code for deletion.';
    header('Location: ' . BASE_URL . '/public/category-mgt/');
    exit;
}

$service = new \App\AssetClassificationService($pdo);
$result = $service->deleteAssetLookup($assetCode);

if ($result['success']) {
    $_SESSION['flash_success'] = "Asset type {$assetCode} deleted successfully.";
} else {
    $_SESSION['flash_error'] = $result['error'] ?? 'Failed to delete asset type.';
}

header('Location: ' . BASE_URL . '/public/category-mgt/');
exit;
