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
    // USE $pdo FROM init.php!
    global $pdo; 
    $assetService = new \App\AssetService($pdo);

    // Helper: normalize a numeric string to a float-friendly format
    $normalizeNumber = function ($v) {
        $s = trim((string)($v ?? ''));
        if ($s === '') return 0.0;
        // remove common thousands separators and currency symbols
        $s = str_replace([',', ' ', '₱', '$'], '', $s);
        // keep only digits, optional leading -, and decimal dot
        $s = preg_replace('/[^0-9.\-]/', '', $s);
        if ($s === '' || $s === '.' || $s === '-' ) return 0.0;
        return (float)$s;
    };

    // 1. Collect and sanitize input
    $data = [
        'reference_no'            => $_POST['reference_no'] ?? null,
        'main_zone_code'          => $_POST['main_zone_code'] ?? '',
        'zone_code'               => $_POST['zone_code'] ?? '',
        'region_code'             => $_POST['region_code'] ?? '',
        'cost_center_code'        => $_POST['cost_center_code'] ?? '',
        'branch_name'             => $_POST['branch_name'] ?? '',
        'group_code'              => $_POST['group_code'] ?? '',
        'asset_code'              => $_POST['asset_code'] ?? '',
        'depreciation_code'       => $_POST['depreciation_code'] ?? '',
        'description'             => trim($_POST['description'] ?? ''),
        'serial_number'           => $_POST['serial_number'] ?? null,
        'quantity'                => (int)($_POST['quantity'] ?? 1),
        'property_type'           => $_POST['property_type'] ?? 'PURCHASED',
        'date_received'           => $_POST['date_received'] ?? '',
        'depreciation_start_date' => $_POST['depreciation_start_date'] ?? '',
        'depreciation_end_date'   => $_POST['depreciation_end_date'] ?? '',
        'depreciation_on'         => $_POST['depreciation_on'] ?? 'LAST_DAY',
        'depreciation_day'        => !empty($_POST['depreciation_day']) ? (int)$_POST['depreciation_day'] : null,
        'acquisition_cost'        => $normalizeNumber($_POST['acquisition_cost'] ?? 0),
        'cost_unit'               => $normalizeNumber($_POST['cost_unit'] ?? 0),
        'item_code'               => $_POST['item_code'] ?? null,
        'monthly_depreciation'    => $normalizeNumber($_POST['monthly_depreciation'] ?? 0),
        'status'                  => $_POST['status'] ?? 'ACTIVE'
    ];

    // 2. Auto-generate the System Asset Code 
    $year = date('Y');
    $rand = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $data['system_asset_code'] = "LRI-{$data['main_zone_code']}-{$data['zone_code']}-{$year}{$rand}";

    // 2b. Server-side fallback for monthly depreciation if client value is missing/zero
    if ($data['monthly_depreciation'] <= 0 && $data['acquisition_cost'] > 0 && !empty($data['group_code'])) {
        $stmt = $pdo->prepare("SELECT actual_months FROM asset_groups WHERE group_code = :group_code LIMIT 1");
        $stmt->execute([':group_code' => $data['group_code']]);
        $actualMonths = (int)($stmt->fetchColumn() ?: 0);

        if ($actualMonths > 0) {
            $data['monthly_depreciation'] = round($data['acquisition_cost'] / $actualMonths, 2);
        }
    }

    // 3. Save to Database
    $result = $assetService->createAsset($data, $_SESSION['user_id']);

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