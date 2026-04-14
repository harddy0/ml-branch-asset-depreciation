<?php
/**
 * public/api/get_group_details.php
 * 
 * Accepts:  GET ?group_code=OE24MOS
 * Returns:  JSON with full classification chain:
 *           group → assets_lookup → amortization_depreciation
 */

$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/AssetClassificationService.php';

ini_set('display_errors', '0');
error_reporting(0);
while (ob_get_level()) { ob_end_clean(); }

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$groupCode = trim($_GET['group_code'] ?? '');

if (empty($groupCode)) {
    echo json_encode(['success' => false, 'error' => 'Missing group_code parameter.']);
    exit;
}

try {
    $classService = new \App\AssetClassificationService($pdo);
    $details = $classService->getGroupDetailsForAssetCreation($groupCode);

    if (!$details) {
        echo json_encode(['success' => false, 'error' => 'Group not found.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data'    => [
            'group_code'                => $details['group_code'],
            'group_name'                => $details['group_name'],
            'actual_months'             => $details['actual_months'],
            'asset_code'                => $details['asset_code'],
            'asset_name'                => $details['asset_name'],
            'depreciation_code'         => $details['depreciation_code'],
            'depreciation_description'  => $details['depreciation_description'],
        ]
    ]);

} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
exit;