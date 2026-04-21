<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';

while (ob_get_level()) { ob_end_clean(); }
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$code = trim((string)($_GET['code'] ?? ''));
if ($code === '') {
    echo json_encode(['success' => false, 'error' => 'Missing asset code.']);
    exit;
}

try {
    global $pdo;
    $assetService = new \App\AssetService($pdo);
    $row = $assetService->getAssetByCode($code);

    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Asset not found.']);
        exit;
    }

    $assetMonths = (int)($row['months'] ?? $row['policy_months'] ?? 0);
    $row['asset_life_months'] = $assetMonths;

    $row['period_depreciation_expense'] = (isset($row['acquisition_cost']) && $assetMonths > 0)
        ? round($row['acquisition_cost'] / max(1, $assetMonths), 2)
        : 0.00;

    if (empty($row['monthly_depreciation'])) {
        $row['monthly_depreciation'] = $row['period_depreciation_expense'];
    }

    if (!isset($row['remaining_life']) && isset($row['accumulated_depreciation']) && isset($row['acquisition_cost']) && $assetMonths > 0) {
        $per = $row['acquisition_cost'] / max(1, $assetMonths);
        $row['remaining_life'] = ($per > 0) ? max(0, $assetMonths - (int)round($row['accumulated_depreciation'] / $per)) : 0;
    }

    echo json_encode(['success' => true, 'row' => $row]);
    exit;
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    exit;
}