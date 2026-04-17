<?php
// API endpoint: Delete GL code
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson(['success' => false, 'error' => 'Invalid request method.'], 405);
}

$glCode = trim($_POST['gl_code'] ?? '');

if (empty($glCode)) {
    respondJson(['success' => false, 'error' => 'GL code is required.'], 422);
}

try {
    $service = new GlCodeService($pdo);
    $result = $service->deleteGlCode($glCode);

    if ($result['success']) {
        respondJson(['success' => true, 'message' => 'GL code deleted successfully.']);
    } else {
        respondJson(['success' => false, 'error' => $result['error'] ?? 'Failed to delete GL code.'], 400);
    }
} catch (Throwable $e) {
    respondJson(['success' => false, 'error' => 'Server error while deleting GL code.'], 500);
}
