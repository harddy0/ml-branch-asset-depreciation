<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/AssetService.php';

ini_set('display_errors', '0');
error_reporting(0);
while (ob_get_level()) { ob_end_clean(); }

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

try {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;

    $options = [
        'page' => max(1, $page),
        'per_page' => ($perPage > 0) ? $perPage : 50,
        'search' => trim((string)($_GET['search'] ?? '')),
        'group_code' => trim((string)($_GET['group_code'] ?? '')),
        'branch_name' => trim((string)($_GET['branch_name'] ?? '')),
        'sort_by' => (string)($_GET['sort_by'] ?? 'created_at'),
        'sort_dir' => (string)($_GET['sort_dir'] ?? 'DESC'),
    ];

    $service = new \App\AssetService($pdo);
    $result = $service->getDepreciationList($options);

    while (ob_get_level()) { ob_end_clean(); }
    echo json_encode([
        'success' => true,
        'data' => $result['data'],
        'branches' => $result['branches'],
        'pagination' => $result['pagination'],
        'sort' => $result['sort'],
        'filters' => $result['filters'],
    ]);
    exit;
} catch (\Throwable $e) {
    while (ob_get_level()) { ob_end_clean(); }
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    exit;
}
