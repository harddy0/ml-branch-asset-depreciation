<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/ExpenseTypeService.php'; // <-- ADDED: Manually load the class

header('Content-Type: application/json');

try {
    // FIXED: Changed $db to $pdo to match your init.php
    $expenseTypeService = new ExpenseTypeService($pdo); 
    $data = $expenseTypeService->getAllForDropdown();
    
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;