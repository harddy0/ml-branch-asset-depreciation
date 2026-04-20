<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/AssetService.php';

while (ob_get_level()) { ob_end_clean(); }
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

try {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 50)));

    $search     = trim((string)($_GET['search'] ?? ''));
    $groupCode  = trim((string)($_GET['group_code'] ?? ''));
    $branchName = trim((string)($_GET['branch_name'] ?? ''));
    $dateFrom   = trim((string)($_GET['date_from'] ?? ''));
    $dateTo     = trim((string)($_GET['date_to'] ?? ''));
    $status     = trim((string)($_GET['status'] ?? ''));
    $sortBy     = (string)($_GET['sort_by'] ?? 'created_at');
    $sortDir    = strtoupper((string)($_GET['sort_dir'] ?? 'DESC'));

    // Resolve group_code -> asset_group_id when provided
    $assetGroupId = 0;
    if ($groupCode !== '') {
        $stmt = $pdo->prepare('SELECT id FROM asset_groups WHERE group_code = :c OR group_name = :c LIMIT 1');
        $stmt->execute([':c' => $groupCode]);
        $g = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($g) $assetGroupId = (int)$g['id'];
    }

    $options = [
        'page' => $page,
        'per_page' => $perPage,
        'search' => $search,
        'asset_group_id' => $assetGroupId,
        'branch_name' => $branchName,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'status' => $status,
        'sort_by' => $sortBy,
        'sort_dir' => $sortDir,
    ];

    $service = new \App\AssetService($pdo);
    $result = $service->getDepreciationList($options);

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
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    exit;
}
