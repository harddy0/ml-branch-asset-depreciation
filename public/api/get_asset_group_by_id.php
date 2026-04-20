<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
header('Content-Type: application/json');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'Asset Group ID is required.']);
    exit;
}

$assetGroupService = new AssetGroupService($db);

$id = (int)$_GET['id'];
$result = $assetGroupService->getById($id);

if ($result) {
    echo json_encode($result);
} else {
    echo json_encode(['error' => 'Asset Group not found.']);
}
exit;