<?php
// public/api/get_locations.php
require_once __DIR__ . '/../../src/includes/init.php';
$noLayout = true;
header('Content-Type: application/json');

try {
    // Instantiate the service using your master database connection
    $locationService = new \App\LocationMasterService($pdo2 ?? null);

    $response = [
        'success'    => true,
        'branches'   => $locationService->getBranches(),
        'regions'    => $locationService->getRegions(),
        'zones'      => $locationService->getZones(),
        'main_zones' => $locationService->getMainZones(),
    ];

    echo json_encode($response);

} catch (\Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch location data: ' . $e->getMessage()
    ]);
}