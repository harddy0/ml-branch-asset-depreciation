<?php
/**
 * load_depreciation_list_page.php
 * * Controller/Action: Initializes data for the depreciation-list page
 * Responsibility: Normalize filters and load dropdown data for page init
 *
 * Returns: $rawFilters, $filters, $hasFiltersApplied, $zones, $regions, $branches, $assetGroups
 *
 * Usage: require_once 'load_depreciation_list_page.php'; in index.php
 */

require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/AssetReportService.php';
// ADDED: We need the AssetGroupService to fetch the groups
require_once __DIR__ . '/../../src/classes/AssetGroupService.php';

try {
    $groupService = new \App\AssetGroupService($pdo);
    $assetGroups = $groupService->getFilterOptions();
    $reportService = new \App\AssetReportService($pdo, $pdo2);
    // ADDED: Initialize the group service
    $groupService  = new \App\AssetGroupService($pdo);

    $hasFiltersApplied = !empty($_GET);
    $rawFilters = [
        'zone'        => $_GET['zone'] ?? '',
        'region'      => $_GET['region'] ?? '',
        'branch_name' => $_GET['branch_name'] ?? '',
        'as_of_date'  => $_GET['as_of_date'] ?? ''
    ];

    $filters = $rawFilters;
    foreach (['zone', 'region', 'branch_name'] as $k) {
        if (($filters[$k] ?? '') === '__ALL__') {
            $filters[$k] = '';
        }
    }

    $filters['as_of_date'] = trim((string)$filters['as_of_date']);
    if ($filters['as_of_date'] === '') {
        $filters['as_of_date'] = date('Y-m-d');
    }

    $zones    = $reportService->getZones();
    $regions  = $reportService->getRegions($filters['zone']);
    $branches = $reportService->getBranches($filters['zone'], $filters['region']);
    
    // FIX: Fetch the actual asset groups so it doesn't crash the JavaScript
    $assetGroups = $groupService->getGroupsForDropdown();

} catch (\Throwable $e) {
    $hasFiltersApplied = !empty($_GET);
    $rawFilters = [
        'zone'        => $_GET['zone'] ?? '',
        'region'      => $_GET['region'] ?? '',
        'branch_name' => $_GET['branch_name'] ?? '',
        'as_of_date'  => $_GET['as_of_date'] ?? ''
    ];
    $filters = $rawFilters;

    if (empty($filters['as_of_date'])) {
        $filters['as_of_date'] = date('Y-m-d');
    }

    $zones = [];
    $regions = [];
    $branches = [];
    // FIX: Provide a safe fallback empty array if the database fails
    $assetGroups = []; 
    
    error_log('Error loading depreciation list filters: ' . $e->getMessage());
}