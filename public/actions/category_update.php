<?php
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/AssetClassificationService.php';

if (!$auth->isAdmin()) {
    header('Location: ' . BASE_URL . '/public/dashboard/'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/category-mgt/'); exit;
}

$id           = (int)($_POST['id'] ?? 0);
$groupCode    = strtoupper(trim($_POST['group_code'] ?? ''));
$groupName    = trim($_POST['group_name'] ?? '');
$actualMonths = (int)($_POST['actual_months'] ?? 0);
$assetCode    = strtoupper(trim($_POST['asset_code'] ?? ''));

if ($id < 1 || $groupCode === '' || $groupName === '' || $actualMonths < 1 || $assetCode === '') {
    $_SESSION['flash_error'] = 'Please complete all required asset group fields.';
    header('Location: ' . BASE_URL . '/public/category-mgt/'); exit;
}

if (!preg_match('/^[A-Z0-9_-]+$/', $groupCode)) {
    $_SESSION['flash_error'] = 'Group code may only contain letters, numbers, underscore, and hyphen.';
    header('Location: ' . BASE_URL . '/public/category-mgt/'); exit;
}

$service = new \App\AssetClassificationService($pdo);
$result = $service->updateAssetGroup($id, [
    'group_code'    => $groupCode,
    'group_name'    => $groupName,
    'actual_months' => $actualMonths,
    'asset_code'    => $assetCode,
]);

if ($result['success']) {
    $_SESSION['flash_success'] = "Asset group {$groupCode} updated successfully.";
} else {
    $_SESSION['flash_error'] = $result['error'] ?? 'Failed to update asset group.';
}

header('Location: ' . BASE_URL . '/public/category-mgt/');
exit;