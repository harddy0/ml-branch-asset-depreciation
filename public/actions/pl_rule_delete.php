<?php
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/AssetClassificationService.php';

if (!$auth->isAdmin()) {
    header('Location: ' . BASE_URL . '/public/dashboard/'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/category-mgt/'); exit;
}

$code = trim($_POST['depreciation_code'] ?? '');

if ($code === '') {
    $_SESSION['flash_error'] = 'Invalid category provided for deletion.';
    header('Location: ' . BASE_URL . '/public/category-mgt/'); exit;
}

$service = new \App\AssetClassificationService($pdo);
$result = $service->deleteAmortizationRule($code);

if ($result['success']) {
    $_SESSION['flash_success'] = "Category deleted successfully.";
} else {
    $_SESSION['flash_error'] = $result['error'];
}

header('Location: ' . BASE_URL . '/public/category-mgt/');
exit;