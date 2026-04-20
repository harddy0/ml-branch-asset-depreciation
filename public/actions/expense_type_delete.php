<?php
$noLayout = true; // MUST BE AT TOP
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/ExpenseTypeService.php';

if (ob_get_length()) { ob_clean(); }

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method. POST required.']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'A valid ID is required for deletion.']);
    exit;
}

try {
    $expenseService = new ExpenseTypeService($pdo);
    $result = $expenseService->deleteExpenseType($id);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Expense type deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete the record. It may not exist.']);
    }
} catch (PDOException $e) {
    if (ob_get_length()) { ob_clean(); }
    if ($e->getCode() == 23000) { 
        echo json_encode(['success' => false, 'message' => 'Cannot delete this Expense Type because it is currently linked to existing assets.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    if (ob_get_length()) { ob_clean(); }
    echo json_encode(['success' => false, 'message' => 'System Error: ' . $e->getMessage()]);
}
exit;