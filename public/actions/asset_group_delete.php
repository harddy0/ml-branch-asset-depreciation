<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Asset Group ID is required for deletion.']);
    exit;
}

$assetGroupService = new \App\AssetGroupService($pdo);

$id = (int)$_POST['id'];
$result = $assetGroupService->delete($id);

echo json_encode($result);
exit;