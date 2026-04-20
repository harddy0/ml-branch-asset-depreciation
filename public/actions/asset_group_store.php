<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Assuming $db is initialized in init.php
$assetGroupService = new AssetGroupService($db);

$result = $assetGroupService->create($_POST);

echo json_encode($result);
exit;