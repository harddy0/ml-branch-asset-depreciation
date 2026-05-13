<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/IssuanceReportService.php';

if (!$auth->isLoggedIn()) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access Denied');
}

$filters = [
    'search' => $_GET['search'] ?? '',
    'zone' => $_GET['zone'] ?? '',
    'region' => $_GET['region'] ?? '',
    'branch_name' => $_GET['branch_name'] ?? '',
    'product_category' => $_GET['product_category'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'transfer_status' => $_GET['transfer_status'] ?? '',
    'sort_by' => $_GET['sort_by'] ?? 'date_issued',
    'sort_dir' => $_GET['sort_dir'] ?? 'DESC',
];

if (ob_get_length()) {
    ob_clean();
}

$service = new \App\IssuanceReportService($pdo);
$service->exportToExcel($filters);