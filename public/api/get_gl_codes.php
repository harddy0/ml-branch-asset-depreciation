<?php
// API endpoint: Get paginated GL codes
$noLayout = true;
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/GlCodeService.php';

use App\GlCodeService;

// Prevent warnings/notices from corrupting JSON payloads for AJAX callers.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

function respondJson(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    if (function_exists('ob_get_level') && ob_get_level() > 0) {
        ob_clean();
    }
    echo json_encode($payload);
    exit;
}

// Parse pagination params
$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
$search = trim($_GET['search'] ?? '');
$glCode = trim($_GET['gl_code'] ?? '');

try {
    $service = new GlCodeService($pdo);

    // If gl_code is provided, fetch single GL code
    if (!empty($glCode)) {
        $glCodeData = $service->getGlCode($glCode);
        if ($glCodeData) {
            respondJson([
                'success' => true,
                'data' => $glCodeData
            ]);
        } else {
            respondJson([
                'success' => false,
                'error' => 'GL code not found'
            ], 404);
        }
    }

    // Otherwise, fetch paginated list
    $result = $service->getPaginatedGlCodes($limit, $offset, $search);
    respondJson([
        'success' => true,
        'data' => $result['data'],
        'total' => $result['total'],
        'limit' => $limit,
        'offset' => $offset
    ]);
} catch (Throwable $e) {
    respondJson([
        'success' => false,
        'error' => 'Failed to fetch GL codes.'
    ], 500);
}
