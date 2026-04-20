<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/GlCodeService.php';

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $service = new \App\GlCodeService($pdo);

    // Single-record mode used by edit modal loader.
    if (isset($_GET['gl_code']) && trim((string)$_GET['gl_code']) !== '') {
        $glCode = trim((string)$_GET['gl_code']);
        $record = $service->getGlCode($glCode);
        if (!$record) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'GL code not found.']);
            exit;
        }

        echo json_encode(['success' => true, 'data' => $record]);
        exit;
    }

    // Paged/list mode used by GL Codes table.
    $hasListParams = isset($_GET['limit']) || isset($_GET['offset']) || isset($_GET['search']);
    if ($hasListParams) {
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);
        $search = trim((string)($_GET['search'] ?? ''));
        $type = isset($_GET['type']) ? strtoupper(trim((string)$_GET['type'])) : '';

        if ($limit < 1) {
            $limit = 20;
        }
        if ($limit > 200) {
            $limit = 200;
        }
        if ($offset < 0) {
            $offset = 0;
        }

        $result = $service->getPaginatedGlCodes($limit, $offset, $search, $type);
        echo json_encode([
            'success' => true,
            'data' => $result['data'],
            'total' => (int)$result['total'],
        ]);
        exit;
    }

    // Backward-compatible plain-array mode used by dropdown fetches.
    $codes = $service->getAllGlCodes();
    if (isset($_GET['type'])) {
        $type = strtoupper(trim((string)$_GET['type']));
        $codes = array_values(array_filter($codes, function ($code) use ($type) {
            return strtoupper((string)($code['account_type'] ?? '')) === $type;
        }));
    }

    echo json_encode($codes);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch GL codes.']);
}