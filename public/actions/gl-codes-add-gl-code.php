<?php
declare(strict_types=1);

use App\GlCodeService;

// Must be set before init so shutdown/layout middleware does not wrap this endpoint.
$noLayout = true;

require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/GlCodeService.php';

header('Content-Type: application/json; charset=utf-8');

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

$glCode      = trim((string) ($_POST['gl_code'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$accountType = strtoupper(trim((string) ($_POST['account_type'] ?? '')));

if ($glCode === '' || $description === '' || $accountType === '') {
    respondJson(['success' => false, 'error' => 'All fields are required.'], 422);
}

try {
    $service = new GlCodeService($pdo);
    $result = $service->createGlCode($glCode, $description, $accountType);

    if (!empty($result['success'])) {
        respondJson(['success' => true, 'message' => 'GL code added successfully.']);
    }

    respondJson(['success' => false, 'error' => $result['error'] ?? 'Unknown error.'], 400);
} catch (\Throwable $e) {
    respondJson(['success' => false, 'error' => 'Server error while adding GL code.'], 500);
}
