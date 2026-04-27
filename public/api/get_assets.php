<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/AssetReportService.php';
require_once __DIR__ . '/../../src/classes/LocationMasterService.php';

ini_set('display_errors', '0');
error_reporting(0);
while (ob_get_level()) { ob_end_clean(); }

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    while (ob_get_level() > 0) ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

try {
    $reportService = new \App\AssetReportService($pdo, $pdo2);
    $locationService = new \App\LocationMasterService($pdo2 ?? null);

    $normalizeDate = static function (string $raw): string {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        $ts = strtotime($raw);
        if ($ts === false) {
            return '';
        }
        return date('Y-m-d', $ts);
    };

    // Normalize inputs; treat '__ALL__' and empty strings as null
    $rawZone   = trim((string)($_GET['zone'] ?? ''));
    $rawRegion = trim((string)($_GET['region'] ?? ''));
    $rawBranch = trim((string)($_GET['branch_name'] ?? ''));

    $zone   = ($rawZone === '__ALL__' || $rawZone === '') ? null : $rawZone;
    $region = ($rawRegion === '__ALL__' || $rawRegion === '') ? null : $rawRegion;
    $branch = ($rawBranch === '__ALL__' || $rawBranch === '') ? null : $rawBranch;

    $asOfDate = $normalizeDate((string)($_GET['as_of_date'] ?? ''));
    $dateFrom = $normalizeDate((string)($_GET['date_from'] ?? ''));
    $dateTo   = $normalizeDate((string)($_GET['date_to'] ?? ''));

    // If no explicit as_of_date or date range provided, leave as empty so
    // the report service returns an empty dataset (table remains empty)
    if ($asOfDate === '') {
        if ($dateTo !== '') {
            $asOfDate = $dateTo;
        } elseif ($dateFrom !== '') {
            $asOfDate = $dateFrom;
        } else {
            $asOfDate = '';
        }
    }

    $filters = [
        'zone'        => $zone,
        'region'      => $region,
        'branch_name' => $branch,
        'as_of_date'  => $asOfDate,
    ];

    $reportData = $reportService->getFilteredAssetsForManageAssets($filters);
    try {
        $regions = $locationService->getRegionOptions($filters['zone']);
    } catch (\Throwable $regionError) {
        $regions = [];
        foreach ($reportService->getRegions($filters['zone']) as $row) {
            $code = trim((string)($row['region_code'] ?? ''));
            if ($code === '') {
                continue;
            }

            $description = trim((string)($row['region_description'] ?? ''));
            $label = $description !== '' ? ($code . ' - ' . $description) : $code;
            $regions[] = [
                'value' => $code,
                'text' => $label,
                'label' => $label,
                'description' => $description,
            ];
        }
    }
    $branches = $reportService->getBranches($filters['zone'], $filters['region']);

    $response = [
        'success'  => true,
        'data'     => $reportData['data'],
        'totals'   => $reportData['totals'],
        'regions'  => $regions,
        'branches' => $branches,
    ];

    while (ob_get_level() > 0) ob_end_clean();
    echo json_encode($response);
    exit;

} catch (\Exception $e) {
    while (ob_get_level() > 0) ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    exit;
}