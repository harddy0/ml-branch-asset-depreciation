<?php
$noLayout = true; 
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/ExpenseTypeService.php';

header('Content-Type: application/json');

$expenseService = new ExpenseTypeService($pdo);

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;
$offset = ($page - 1) * $limit;

try {
    $data = $expenseService->getExpenseTypes($search, $limit, $offset, $category);
    $totalRecords = $expenseService->getTotalCount($search, $category);
    $totalPages = ceil($totalRecords / $limit);

    // FIX: Destroy all output buffers to wipe out the BOM or whitespace
    while (ob_get_level()) {
        ob_end_clean();
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'limit' => $limit
        ]
    ]);
    exit;
} catch (Exception $e) {
    while (ob_get_level()) { ob_end_clean(); }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}