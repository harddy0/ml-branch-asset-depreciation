<?php
$noLayout = true; 
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/ExpenseTypeService.php';

if (ob_get_length()) {
    ob_clean();
}

header('Content-Type: application/json');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID is required']);
    exit;
}

try {
    $expenseService = new ExpenseTypeService($pdo);
    $expenseType = $expenseService->getExpenseTypeById((int)$_GET['id']);

    if ($expenseType) {
        echo json_encode(['success' => true, 'data' => $expenseType]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Expense type not found']);
    }
} catch (Exception $e) {
    if (ob_get_length()) { ob_clean(); }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
exit;