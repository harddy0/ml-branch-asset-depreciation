<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/AssetReportService.php';

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

    // Normalize inputs; treat '__ALL__' and empty strings as null
    $rawZone   = trim((string)($_GET['zone'] ?? ''));
    $rawRegion = trim((string)($_GET['region'] ?? ''));
    $rawBranch = trim((string)($_GET['branch_name'] ?? ''));

    $zone   = ($rawZone === '__ALL__' || $rawZone === '') ? null : $rawZone;
    $region = ($rawRegion === '__ALL__' || $rawRegion === '') ? null : $rawRegion;
    $branch = ($rawBranch === '__ALL__' || $rawBranch === '') ? null : $rawBranch;

    $asOfDate = trim((string)($_GET['as_of_date'] ?? ''));
    $dateFrom = trim((string)($_GET['date_from'] ?? ''));
    $dateTo   = trim((string)($_GET['date_to'] ?? ''));

    if ($asOfDate === '') {
        $asOfDate = $dateTo !== '' ? $dateTo : ($dateFrom !== '' ? $dateFrom : date('Y-m-d'));
    }

    $filters = [
        'zone'        => $zone,
        'region'      => $region,
        'branch_name' => $branch,
        'as_of_date'  => $asOfDate,
    ];

    $reportData = $reportService->getFilteredAssets($filters);
    $regions  = $reportService->getRegions($filters['zone']);
    $branches = $reportService->getBranches($filters['zone'], $filters['region']);

    $response = [
        'success'  => true,
        'data'     => $reportData['data'],
        'totals'   => $reportData['totals'],
        'regions'  => $regions,
        'branches' => $branches,
    ];

    while (ob_get_level() > 0) ob_end_clean();
    echo json_encode($response);
    exit;

} catch (\Exception $e) {
    while (ob_get_level() > 0) ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    exit;
}