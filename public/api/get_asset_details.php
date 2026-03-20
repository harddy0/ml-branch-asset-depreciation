<?php
// public/api/get_asset_details.php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';

// Ensure API responses are clean JSON: disable display of PHP errors and
// clear any output buffers that init.php may have started (layout injection).
ini_set('display_errors', '0');
error_reporting(0);
while (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$code = $_GET['code'] ?? '';
if (!$code) {
    echo json_encode(['success' => false, 'error' => 'Missing asset code.']);
    exit;
}

try {
    // Fetch asset by system_asset_code and the latest running_depreciation row
    $sql = "SELECT a.*, c.category_name, c.category_code, c.asset_life_months,
                   bp.zone, bp.region, bp.cost_center, bp.branch_code,
                   rd.accumulated_depreciation, rd.book_value, rd.period_date
            FROM assets a
            LEFT JOIN asset_categories c ON a.category_code = c.category_code
            LEFT JOIN branch_profile bp ON a.branch_name = bp.branch_name
            LEFT JOIN running_depreciation rd ON a.id = rd.asset_id
            WHERE a.system_asset_code = :code
            ORDER BY rd.period_date DESC
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':code' => $code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Asset not found.']);
        exit;
    }


    // Normalize field names to match import-preview shape expected by renderDeprDetails()
    // date_received: prefer asset column, else empty
    $row['date_received'] = $row['date_received'] ?? ($row['date_received'] ?? null);
    // depreciation_start: assets column might be 'depreciation_start_date'
    if (isset($row['depreciation_start_date']) && !$row['depreciation_start']) {
        $row['depreciation_start'] = $row['depreciation_start_date'];
    }
    // cost_center: normalize from asset or branch_profile
    if (empty($row['cost_center'])) {
        if (!empty($row['cost_center_code'])) $row['cost_center'] = $row['cost_center_code'];
        elseif (!empty($row['cost_center'])) $row['cost_center'] = $row['cost_center'];
    }
    // branch_code
    if (empty($row['branch_code']) && !empty($row['branch_code'])) $row['branch_code'] = $row['branch_code'];

    // Compute derived depreciation fields
    $row['period_depreciation_expense'] = isset($row['acquisition_cost'], $row['asset_life_months']) && $row['asset_life_months'] > 0
        ? ($row['acquisition_cost'] / max(1, $row['asset_life_months']))
        : 0;

    if (empty($row['monthly_depreciation'])) $row['monthly_depreciation'] = $row['period_depreciation_expense'];

    // remaining_life: compute if missing
    if (!isset($row['remaining_life']) && isset($row['accumulated_depreciation']) && isset($row['acquisition_cost']) && isset($row['asset_life_months'])) {
        $per = $row['acquisition_cost'] / max(1, $row['asset_life_months']);
        $row['remaining_life'] = ($per > 0) ? ($row['asset_life_months'] - round($row['accumulated_depreciation'] / $per)) : 0;
    }

    // Ensure keys commonly used by renderDeprDetails exist (fallbacks)
    $row['reference_no'] = $row['reference_no'] ?? ($row['reference'] ?? '');
    $row['description'] = $row['description'] ?? '';
    $row['category_name'] = $row['category_name'] ?? '';
    $row['category_code'] = $row['category_code'] ?? '';

    echo json_encode(['success' => true, 'row' => $row]);
    exit;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
