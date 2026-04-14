<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/AssetClassificationService.php';

ini_set('display_errors', '0');
error_reporting(0);
while (ob_get_level()) { ob_end_clean(); }

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method.']);
    exit;
}

try {
    $service = new \App\AssetClassificationService($pdo);

    echo json_encode([
        'plRules'     => $service->getAllAmortizationRules(),
        'assetTypes'  => $service->getAllAssetLookups(),
        'assetGroups' => $service->getAllAssetGroups(),
    ]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

exit;
