<?php
/**
 * get_asset_group_filter_options.php
 *
 * API Endpoint: Fetch asset group options for the depreciation list asset group filter.
 * Response: { success: true, data: [{ id, label, group_name }] }
 */

$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/AssetGroupService.php';

while (ob_get_level()) { ob_end_clean(); }
header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new \Exception('Invalid request method. Only GET is allowed.');
    }

    $groupService = new \App\AssetGroupService($pdo);
    $options = $groupService->getFilterOptions();

    echo json_encode([
        'success' => true,
        'data' => $options,
    ]);
    exit;
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
    exit;
}
