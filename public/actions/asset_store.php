<?php
// Prevent init.php from injecting HTML layout around our JSON response
$noLayout = true; 
require_once __DIR__ . '/../../src/includes/init.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit;
}

try {
    global $pdo;
    $assetService = new \App\AssetService($pdo);

    // Build payload matching AssetService expectations
    $assetGroupId = isset($_POST['asset_group_id']) ? (int)$_POST['asset_group_id'] : 0;

    // If UI sends a group code instead of ID, attempt to resolve it
    if ($assetGroupId <= 0 && !empty($_POST['group_code'])) {
        try {
            $stmt = $pdo->prepare('SELECT id FROM asset_groups WHERE group_code = :code OR group_name = :code LIMIT 1');
            $stmt->execute([':code' => trim((string)$_POST['group_code'])]);
            $g = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($g) $assetGroupId = (int)$g['id'];
        } catch (\Throwable $e) {
            // ignore resolution errors; service will validate
        }
    }

    $sanitized = [
        'asset_group_id'          => $assetGroupId,
        'system_asset_code'       => $_POST['system_asset_code'] ?? null,
        'reference_no'            => $_POST['reference_no'] ?? null,
        'main_zone_code'          => trim((string)($_POST['main_zone_code'] ?? '')) ?: null,
        'zone_code'               => trim((string)($_POST['zone_code'] ?? '')) ?: null,
        'region_code'             => substr(trim((string)($_POST['region_code'] ?? '')), 0, 100) ?: null,
        'cost_center_code'        => trim((string)($_POST['cost_center_code'] ?? '')) ?: null,
        'branch_name'             => trim((string)($_POST['branch_name'] ?? '')) ?: null,
        'asset_name'              => trim((string)($_POST['asset_name'] ?? $_POST['description'] ?? '')),
        'months'                  => (int)($_POST['months'] ?? 0),
        'description'             => trim((string)($_POST['description'] ?? '')),
        'serial_number'           => $_POST['serial_number'] ?? null,
        'item_code'               => $_POST['item_code'] ?? null,
        'quantity'                => max(1, (int)($_POST['quantity'] ?? 1)),
        'property_type'           => $_POST['property_type'] ?? 'PURCHASED',
        'date_received'           => (!empty($_POST['date_received']) ? $_POST['date_received'] : null),
        'depreciation_start_date' => (!empty($_POST['depreciation_start_date']) ? $_POST['depreciation_start_date'] : null),
        'depreciation_end_date'   => (!empty($_POST['depreciation_end_date']) ? $_POST['depreciation_end_date'] : null),
        'depreciation_on'         => $_POST['depreciation_on'] ?? 'LAST_DAY',
        'depreciation_day'        => isset($_POST['depreciation_day']) && $_POST['depreciation_day'] !== '' ? (int)$_POST['depreciation_day'] : null,
        'acquisition_cost'        => $_POST['acquisition_cost'] ?? 0,
        'cost_unit'               => $_POST['cost_unit'] ?? 0,
        'status'                  => $_POST['status'] ?? 'ACTIVE'
    ];

    $result = $assetService->createAssetFromRequest($sanitized, $_SESSION['user_id']);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Asset successfully saved.',
            'asset_id' => $result['asset_id']
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $result['error']]);
    }

} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server Error: ' . $e->getMessage()]);
}