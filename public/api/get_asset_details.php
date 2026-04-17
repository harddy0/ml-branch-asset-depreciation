<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';

ini_set('display_errors', '0');
error_reporting(0);
while (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$code = $_GET['code'] ?? '';
if (!$code) {
    echo json_encode(['success' => false, 'error' => 'Missing asset code.']);
    exit;
}

try {
    global $pdo;
    $assetService = new \App\AssetService($pdo);
    $row = $assetService->getAssetDetailsByCode($code);

    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Asset not found.']);
        exit;
    }

    // Map cost_center_code so JS can read it
    if (empty($row['cost_center']) && !empty($row['cost_center_code'])) {
        $row['cost_center'] = $row['cost_center_code'];
    }

    // Calculate Depreciation
    $row['period_depreciation_expense'] = isset($row['acquisition_cost'], $row['asset_life_months']) && $row['asset_life_months'] > 0
        ? ($row['acquisition_cost'] / max(1, $row['asset_life_months']))
        : 0;

    if (empty($row['monthly_depreciation'])) $row['monthly_depreciation'] = $row['period_depreciation_expense'];

    if (!isset($row['remaining_life']) && isset($row['accumulated_depreciation']) && isset($row['acquisition_cost']) && isset($row['asset_life_months'])) {
        $per = $row['acquisition_cost'] / max(1, $row['asset_life_months']);
        $row['remaining_life'] = ($per > 0) ? ($row['asset_life_months'] - round($row['accumulated_depreciation'] / $per)) : 0;
    }

    echo json_encode(['success' => true, 'row' => $row]);
    exit;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}