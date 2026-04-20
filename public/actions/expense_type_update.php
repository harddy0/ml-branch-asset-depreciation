<?php
// 1. CRITICAL: This must be the very first line before init.php to stop the HTML layout
$noLayout = true; 
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/ExpenseTypeService.php';

// 2. CRITICAL: Empty the output buffer of any hidden characters or BOMs
if (ob_get_length()) {
    ob_clean();
}

header('Content-Type: application/json');

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method. POST required.']);
    exit;
}

// Sanitize and capture inputs
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$expenseName = isset($_POST['expense_name']) ? trim($_POST['expense_name']) : '';
$categoryType = isset($_POST['category_type']) ? trim($_POST['category_type']) : '';
$policyMonths = isset($_POST['policy_months']) ? (int)$_POST['policy_months'] : 0;

// Validation
if ($id <= 0 || empty($expenseName) || empty($categoryType) || $policyMonths <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data. All fields are required.']);
    exit;
}

try {
    $expenseService = new ExpenseTypeService($pdo);
    $result = $expenseService->updateExpenseType($id, $expenseName, $categoryType, $policyMonths);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Expense type updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes were made or update failed.']);
    }
} catch (Exception $e) {
    // Clean buffer again just in case the error threw something unexpected
    if (ob_get_length()) { ob_clean(); }
    echo json_encode(['success' => false, 'message' => 'System Error: ' . $e->getMessage()]);
}

// 3. CRITICAL: Stop execution here so nothing else can append to the file
exit;