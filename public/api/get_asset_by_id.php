<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/AssetService.php';

// Clear buffers to prevent HTML bleeding into JSON
while (ob_get_level()) { ob_end_clean(); }
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid Asset ID provided.']);
        exit;
    }

    $service = new \App\AssetService($pdo);
    $asset = $service->getAssetById($id);

    if (!$asset) {
        echo json_encode(['success' => false, 'error' => 'Asset not found.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => $asset
    ]);
    exit;

} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    exit;
}