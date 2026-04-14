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
    $assetId = isset($_GET['asset_id']) ? (int)$_GET['asset_id'] : 0;
    if ($assetId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid asset_id.']);
        exit;
    }

    $filters = [
        'date_from' => trim((string)($_GET['date_from'] ?? '')),
        'date_to' => trim((string)($_GET['date_to'] ?? '')),
        'entry_side' => trim((string)($_GET['entry_side'] ?? 'ALL')),
        'period_year' => (int)($_GET['period_year'] ?? 0),
        'period_month' => (int)($_GET['period_month'] ?? 0),
    ];

    $service = new \App\LedgerReportService($pdo);
    $report = $service->getAssetLedgerReport($assetId, $filters);

    if (!$report['asset']) {
        echo json_encode(['success' => false, 'error' => 'Asset not found.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'asset' => $report['asset'],
        'ledger_rows' => $report['ledger_rows'],
        'fs_rows' => $report['fs_rows'],
        'totals' => $report['totals'],
        'filters' => $report['filters'],
        'options' => $report['options'],
        'meta' => [
            'generated_by' => strtoupper((string)($_SESSION['full_name'] ?? 'User')),
            'generated_at' => date('Y-m-d H:i:s'),
        ],
    ]);
    exit;
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    exit;
}
