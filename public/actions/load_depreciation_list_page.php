<?php
/**
 * load_depreciation_list_page.php
 * 
 * Controller/Action: Initializes data for the depreciation-list page
 * Responsibility: Load asset groups dropdown and other page-init data
 * 
 * Returns: $assetGroups (array of [group_code, group_name])
 * 
 * Usage: require_once 'load_depreciation_list_page.php'; in index.php
 */

require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/AssetClassificationService.php';

try {
    $classService = new \App\AssetClassificationService($pdo);
    $assetGroups = $classService->getDropdownOptions();
    // Returns: [['group_code' => '...', 'group_name' => '...'], ...]
} catch (\Throwable $e) {
    // If loading fails, provide empty array so page still loads
    $assetGroups = [];
    error_log('Error loading asset groups: ' . $e->getMessage());
}
