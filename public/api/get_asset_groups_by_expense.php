<?php
use App\AssetGroupService;

$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
header('Content-Type: application/json');

if (!isset($_GET['expense_type_id']) || empty($_GET['expense_type_id'])) {
    echo json_encode([]);
    exit;
}

$assetGroupService = new AssetGroupService($pdo);

$expenseTypeId = (int)$_GET['expense_type_id'];
$result = $assetGroupService->getByExpenseType($expenseTypeId);

echo json_encode($result);
exit;