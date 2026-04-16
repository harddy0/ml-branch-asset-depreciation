<?php
/**
 * public/api/get_locations.php
 *
 * Supports 2 response modes:
 * 1) Full payload (legacy/manual add):
 *      GET /public/api/get_locations.php
 *      -> { success, branches, regions, zones, main_zones }
 * 2) Cascaded payload (import edit modal):
 *      GET /public/api/get_locations.php?level=main_zones|zones|regions|branches&filter=...
 *      -> { success, data: [{ value, label, ...meta }] }
 */

$noLayout = true;

require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/LocationMasterService.php';

ini_set('display_errors', '0');
error_reporting(0);
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

try {
    $locationService = new \App\LocationMasterService($pdo2 ?? null);

    $level  = strtolower(trim((string)($_GET['level'] ?? '')));
    $filter = trim((string)($_GET['filter'] ?? ''));

    // Legacy/full mode used by manual add asset screen.
    if ($level === '') {
        echo json_encode([
            'success'    => true,
            'branches'   => $locationService->getBranches(),
            'regions'    => $locationService->getRegions(),
            'zones'      => $locationService->getZones(),
            'main_zones' => $locationService->getMainZones(),
        ]);
        exit;
    }

    $data = [];

    if ($level === 'main_zones') {
        foreach ($locationService->getMainZones() as $row) {
            $code = trim((string)($row['main_zone_code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $data[] = [
                'value' => $code,
                'label' => $code,
            ];
        }
    } elseif ($level === 'zones') {
        $rows = $filter !== ''
            ? $locationService->getZonesByMainZone($filter)
            : $locationService->getZones();

        foreach ($rows as $row) {
            $code = trim((string)($row['zone_code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $data[] = [
                'value' => $code,
                'label' => $code,
            ];
        }
    } elseif ($level === 'regions') {
        $rows = $filter !== ''
            ? $locationService->getRegionsByZone($filter)
            : $locationService->getRegions();

        foreach ($rows as $row) {
            $code = trim((string)($row['region_code'] ?? ''));
            if ($code === '') {
                continue;
            }

            $desc  = trim((string)($row['region_description'] ?? ''));
            $label = $desc !== '' ? ($code . ' - ' . $desc) : $code;

            $data[] = [
                'value' => $code,
                'label' => $label,
            ];
        }
    } elseif ($level === 'branches') {
        $rows = $locationService->getBranches($filter !== '' ? $filter : null);

        foreach ($rows as $row) {
            $branchName = trim((string)($row['branch_name'] ?? ''));
            if ($branchName === '') {
                continue;
            }

            $data[] = [
                'value'            => $branchName,
                'label'            => $branchName,
                'cost_center_code' => (string)($row['cost_center_code'] ?? ''),
                'branch_code'      => (string)($row['branch_code'] ?? ($row['cost_center_code'] ?? '')),
                'zone_code'        => (string)($row['zone_code'] ?? ''),
                'main_zone_code'   => (string)($row['main_zone_code'] ?? ''),
                'region_code'      => (string)($row['region_code'] ?? ''),
            ];
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid level parameter.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data'    => $data,
    ]);
} catch (\Throwable $e) {
    echo json_encode([
        'success' => false,
        'error'   => 'Failed to fetch location data: ' . $e->getMessage(),
    ]);
}

exit;
