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

$originalCode     = strtoupper(trim($_POST['original_depreciation_code'] ?? ''));
$depreciationCode = strtoupper(trim($_POST['depreciation_code'] ?? ''));
$description      = trim($_POST['description'] ?? '');
$limitMonths      = (int)($_POST['limit_months'] ?? 0);
$ruleType         = strtoupper(trim($_POST['rule_type'] ?? ''));
$allowedRuleTypes = ['EXACT', 'MAXIMUM', 'MINIMUM'];

if ($originalCode === '' || $depreciationCode === '' || $description === '' || $limitMonths < 1 || !in_array($ruleType, $allowedRuleTypes, true)) {
    $_SESSION['flash_error'] = 'Please complete all required P&L rule fields with valid values.';
    header('Location: ' . BASE_URL . '/public/category-mgt/');
    exit;
}

$service = new \App\AssetClassificationService($pdo);
$result = $service->updateAmortizationRule($originalCode, [
    'depreciation_code' => $depreciationCode,
    'description'       => $description,
    'limit_months'      => $limitMonths,
    'rule_type'         => $ruleType,
]);

if ($result['success']) {
    $_SESSION['flash_success'] = "P&L rule {$depreciationCode} updated successfully.";
} else {
    $_SESSION['flash_error'] = $result['error'] ?? 'Failed to update P&L rule.';
}

header('Location: ' . BASE_URL . '/public/category-mgt/');
exit;
