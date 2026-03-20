<?php
// public/api/get_asset_by_id.php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';

// Ensure API responses are clean JSON
ini_set('display_errors', '0');
error_reporting(0);
while (ob_get_level()) { ob_end_clean(); }
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Missing or invalid id.']);
    exit;
}

try {
    $sql = "SELECT a.*, c.category_name, c.category_code, c.asset_life_months,
                   bp.zone, bp.region, bp.cost_center, bp.branch_code,
                   rd.accumulated_depreciation, rd.book_value, rd.period_date
            FROM assets a
            LEFT JOIN asset_categories c ON a.category_code = c.category_code
            LEFT JOIN branch_profile bp ON a.branch_name = bp.branch_name
            LEFT JOIN running_depreciation rd ON a.id = rd.asset_id
            WHERE a.id = :id
            ORDER BY rd.period_date DESC
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Asset not found.']);
        exit;
    }

    // Normalize field names expected by renderDeprDetails()
    $row['date_received'] = $row['date_received'] ?? ($row['date_received'] ?? null);

    if (isset($row['depreciation_start_date']) && empty($row['depreciation_start'])) {
        $row['depreciation_start'] = $row['depreciation_start_date'];
    }

    if (empty($row['cost_center'])) {
        if (!empty($row['cost_center_code'])) $row['cost_center'] = $row['cost_center_code'];
    }

    if (empty($row['branch_code']) && !empty($row['branch_code'])) $row['branch_code'] = $row['branch_code'];

    $row['period_depreciation_expense'] = isset($row['acquisition_cost'], $row['asset_life_months']) && $row['asset_life_months'] > 0
        ? ($row['acquisition_cost'] / max(1, $row['asset_life_months']))
        : 0;

    if (empty($row['monthly_depreciation'])) $row['monthly_depreciation'] = $row['period_depreciation_expense'];

    if (!isset($row['remaining_life']) && isset($row['accumulated_depreciation']) && isset($row['acquisition_cost']) && isset($row['asset_life_months'])) {
        $per = $row['acquisition_cost'] / max(1, $row['asset_life_months']);
        $row['remaining_life'] = ($per > 0) ? ($row['asset_life_months'] - round($row['accumulated_depreciation'] / $per)) : 0;
    }

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
