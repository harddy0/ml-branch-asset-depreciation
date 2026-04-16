<?php
/**
 * public/api/get_groups.php
 *
 * Returns all asset_groups joined with assets_lookup and amortization_depreciation.
 * Used by the import edit modal to populate the GL Group dropdown and auto-fill GL codes.
 *
 * Response:
 *   {
 *     success: true,
 *     groups: [
 *       {
 *         group_code, group_name, actual_months,
 *         asset_code, asset_name,
 *         depreciation_code, depreciation_description
 *       }, ...
 *     ]
 *   }
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

try {
    $sql = "
        SELECT
            ag.group_code,
            ag.group_name,
            ag.actual_months,
            al.asset_code,
            al.asset_name,
            ad.depreciation_code,
            ad.description AS depreciation_description
        FROM asset_groups ag
        JOIN assets_lookup al ON ag.asset_code = al.asset_code
        JOIN amortization_depreciation ad ON al.depreciation_code = ad.depreciation_code
        ORDER BY ag.group_name ASC
    ";

    $stmt = $pdo->query($sql);
    $groups = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    // Cast actual_months to int for JS math
    foreach ($groups as &$g) {
        $g['actual_months'] = (int)$g['actual_months'];
    }
    unset($g);

    echo json_encode(['success' => true, 'groups' => $groups]);

} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
exit;