<?php
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/AssetClassificationService.php';

if (!$auth->isAdmin()) {
    header('Location: ' . BASE_URL . '/public/dashboard/'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/category-mgt/'); exit;
}

$code        = strtoupper(trim($_POST['depreciation_code'] ?? ''));
$description = trim($_POST['description'] ?? '');
$months      = (int)($_POST['months'] ?? 0);
$glCode      = trim($_POST['gl_code'] ?? '');

if ($code === '' || $description === '' || $months < 1 || $glCode === '') {
    $_SESSION['flash_error'] = 'Please complete all required fields.';
    header('Location: ' . BASE_URL . '/public/category-mgt/'); exit;
}

$service = new \App\AssetClassificationService($pdo);
$result = $service->createAmortizationRule([
    'depreciation_code' => $code,
    'description'       => $description,
    'months'            => $months,
    'gl_code'           => $glCode,
]);

if ($result['success']) {
    $_SESSION['flash_success'] = "Category {$code} created successfully.";
} else {
    $_SESSION['flash_error'] = $result['error'];
}

header('Location: ' . BASE_URL . '/public/category-mgt/');
exit;