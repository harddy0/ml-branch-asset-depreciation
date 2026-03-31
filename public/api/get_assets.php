<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/AssetReportService.php';
require_once __DIR__ . '/../../src/classes/CategoryService.php';

ini_set('display_errors', '0');
error_reporting(0);
while (ob_get_level()) { ob_end_clean(); }

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    while (ob_get_level() > 0) ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

try {
    $reportService = new \App\AssetReportService($pdo, $pdo2);
    $categoryService = new \App\CategoryService($pdo);

    // Normalize __ALL__ sentinel → ''
    $rawZone   = $_GET['zone']        ?? '';
    $rawRegion = $_GET['region']      ?? '';
    $rawBranch = $_GET['branch_name'] ?? '';

    $filters = [
        'zone'        => ($rawZone   === '__ALL__') ? '' : $rawZone,
        'region'      => ($rawRegion === '__ALL__') ? '' : $rawRegion,
        'branch_name' => ($rawBranch === '__ALL__') ? '' : $rawBranch,
        'date_from'   => $_GET['date_from'] ?? date('Y-m-01'),
        'date_to'     => $_GET['date_to']   ?? date('Y-m-t'),
    ];

    foreach (['zone', 'region', 'branch_name'] as $k) {
        if (($filters[$k] ?? '') === '__ALL__') {
            $filters[$k] = '';
        }
    }

    $reportData = $reportService->getFilteredAssets($filters);
    $regions  = $reportService->getRegions($filters['zone']);
    $branches = $reportService->getBranches($filters['zone'], $filters['region']);
    
    // Delegate to Service Class instead of direct $pdo->query
    $allCategories = $categoryService->getAllCategoryNames();

    $response = [
        'success'  => true,
        'data'     => $reportData['data'],
        'totals'   => $reportData['totals'],
        'regions'  => $regions,
        'branches' => $branches,
        'all_categories' => $allCategories, 
    ];

    while (ob_get_level() > 0) ob_end_clean();
    echo json_encode($response);
    exit;

} catch (\Exception $e) {
    while (ob_get_level() > 0) ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    exit;
}