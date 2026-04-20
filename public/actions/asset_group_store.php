<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// `init.php` exposes `$pdo` as the DB connection and services are namespaced under App\
$assetGroupService = new \App\AssetGroupService($pdo);

$result = $assetGroupService->create($_POST);

echo json_encode($result);
exit;