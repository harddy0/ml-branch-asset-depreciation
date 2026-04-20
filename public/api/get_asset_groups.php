<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
header('Content-Type: application/json');

$assetGroupService = new \App\AssetGroupService($pdo);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$result = $assetGroupService->getPaginatedList($page, $limit, $search);

echo json_encode($result);
exit;