<?php
$noLayout = true; // This MUST be at the very top to stop the HTML layout
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/ExpenseTypeService.php';

// Empty the output buffer of any hidden characters or BOMs
if (ob_get_length()) {
    ob_clean();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method. POST required.']);
    exit;
}

$expenseName = isset($_POST['expense_name']) ? trim($_POST['expense_name']) : '';
$categoryType = isset($_POST['category_type']) ? trim($_POST['category_type']) : '';
$policyMonths = isset($_POST['policy_months']) ? (int)$_POST['policy_months'] : 0;

if (empty($expenseName) || empty($categoryType) || $policyMonths <= 0) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

try {
    $expenseService = new ExpenseTypeService($pdo);
    $result = $expenseService->createExpenseType($expenseName, $categoryType, $policyMonths);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Expense type created successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to insert record.']);
    }
} catch (Exception $e) {
    if (ob_get_length()) { ob_clean(); }
    echo json_encode(['success' => false, 'message' => 'System Error: ' . $e->getMessage()]);
}
exit; // Ensure execution stops here