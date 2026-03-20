<?php
// CRITICAL: Prevent init.php from wrapping our Excel binary data in HTML
$noLayout = true; 

require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/AssetReportService.php';

if (!$auth->isLoggedIn()) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access Denied');
}

$filters = [
    'zone'        => $_GET['zone'] ?? '',
    'region'      => $_GET['region'] ?? '',
    'branch_name' => $_GET['branch_name'] ?? '',
    'date_from'   => $_GET['date_from'] ?? '',
    'date_to'     => $_GET['date_to'] ?? ''
];

foreach (['zone', 'region', 'branch_name'] as $k) {
    if (($filters[$k] ?? '') === '__ALL__') {
        $filters[$k] = '';
    }
}

if (empty($filters['date_from']) || empty($filters['date_to'])) {
    die("Error: Date range is required for export.");
}

// Clear any accidental whitespace or notices from the output buffer before generating Excel
if (ob_get_length()) {
    ob_clean();
}

$reportService = new \App\AssetReportService($pdo, $pdo2);
$reportService->exportToExcel($filters);