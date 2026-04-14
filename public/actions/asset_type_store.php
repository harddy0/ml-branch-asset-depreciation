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

$assetCode        = strtoupper(trim($_POST['asset_code'] ?? ''));
$assetName        = trim($_POST['asset_name'] ?? '');
$depreciationCode = strtoupper(trim($_POST['depreciation_code'] ?? ''));

if ($assetCode === '' || $assetName === '' || $depreciationCode === '') {
    $_SESSION['flash_error'] = 'Please complete all required asset type fields.';
    header('Location: ' . BASE_URL . '/public/category-mgt/');
    exit;
}

$service = new \App\AssetClassificationService($pdo);
$result = $service->createAssetLookup([
    'asset_code'        => $assetCode,
    'asset_name'        => $assetName,
    'depreciation_code' => $depreciationCode,
]);

if ($result['success']) {
    $_SESSION['flash_success'] = "Asset type {$assetCode} created successfully.";
} else {
    $_SESSION['flash_error'] = $result['error'] ?? 'Failed to create asset type.';
}

header('Location: ' . BASE_URL . '/public/category-mgt/');
exit;
