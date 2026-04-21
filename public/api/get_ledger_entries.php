<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/LedgerReportService.php';

ini_set('display_errors', '0');
error_reporting(0);
while (ob_get_level()) { ob_end_clean(); }

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

try {
    global $pdo;
    $ledgerService = new \App\LedgerReportService($pdo);

    $filters = [
        'month'      => trim((string)($_GET['month'] ?? 'ALL')),
        'year'       => trim((string)($_GET['year'] ?? 'ALL')),
        'entry_type' => trim((string)($_GET['entry_type'] ?? 'ALL')),
        'asset_id'   => isset($_GET['asset_id']) ? (int)$_GET['asset_id'] : 0
    ];

    $data = $ledgerService->getLedgerEntries($filters);

    echo json_encode([
        'success' => true,
        'data'    => $data,
        'filters' => $filters
    ]);

} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server Error: ' . $e->getMessage()]);
}
exit;