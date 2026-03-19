<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/DashboardService.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    while (ob_get_level() > 0) ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

try {
    $dashService = new \App\DashboardService($pdo);
    
    $response = [
        'success'    => true,
        'zones'      => $dashService->getZoneFinancials(),
        'categories' => $dashService->getCategoryDistribution(),
        'branches'   => $dashService->getTopBranches(5)
    ];

    while (ob_get_level() > 0) ob_end_clean();
    echo json_encode($response);
    exit;

} catch (\Exception $e) {
    while (ob_get_level() > 0) ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    exit;
}