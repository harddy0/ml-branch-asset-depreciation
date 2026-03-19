<?php
// public/api/get_assets.php
$noLayout = true; // Prevent the main template from loading
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/AssetReportService.php';

// Force the browser to expect JSON
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    while (ob_get_level() > 0) ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

try {
    $reportService = new \App\AssetReportService($pdo, $pdo2);

    // Retrieve active filters
    $filters = [
        'zone'        => $_GET['zone'] ?? '',
        'region'      => $_GET['region'] ?? '',
        'branch_name' => $_GET['branch_name'] ?? '',
        'date_from'   => $_GET['date_from'] ?? date('Y-m-01'), 
        'date_to'     => $_GET['date_to'] ?? date('Y-m-t')     
    ];

    // Get table data
    $reportData = $reportService->getFilteredAssets($filters);

    // Get updated dependent dropdowns based on the new selections
    $regions = $reportService->getRegions($filters['zone']);
    $branches = $reportService->getBranches($filters['zone'], $filters['region']);

    // Build the final response array
    $response = [
        'success'  => true,
        'data'     => $reportData['data'],
        'totals'   => $reportData['totals'],
        'regions'  => $regions,
        'branches' => $branches
    ];


    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    echo json_encode($response);
    exit;

} catch (\Exception $e) {
    // If an error happens, we still need to wipe the buffer before sending the JSON error
    while (ob_get_level() > 0) {
        ob_end_clean(); 
    }
    
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    exit;
}