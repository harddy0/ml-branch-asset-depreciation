<?php
/**
 * get_asset_groups_for_dropdown.php
 * 
 * API Endpoint: Fetch all asset groups with GL account details for dropdown population
 * 
 * Used by: add-asset.js modal Step 2 (Details section)
 * Response: { success: true, data: [...asset groups with GL details...] }
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
    $groups = $groupService->getGroupsForDropdown();

    echo json_encode([
        'success' => true,
        'data' => $groups
    ]);
    exit;
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}
