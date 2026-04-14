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

$depreciationCode = strtoupper(trim($_POST['depreciation_code'] ?? ''));

if ($depreciationCode === '') {
    $_SESSION['flash_error'] = 'Missing P&L rule code for deletion.';
    header('Location: ' . BASE_URL . '/public/category-mgt/');
    exit;
}

$service = new \App\AssetClassificationService($pdo);
$result = $service->deleteAmortizationRule($depreciationCode);

if ($result['success']) {
    $_SESSION['flash_success'] = "P&L rule {$depreciationCode} deleted successfully.";
} else {
    $_SESSION['flash_error'] = $result['error'] ?? 'Failed to delete P&L rule.';
}

header('Location: ' . BASE_URL . '/public/category-mgt/');
exit;
