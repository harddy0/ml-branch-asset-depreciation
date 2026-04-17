<?php
declare(strict_types=1);

use App\GlCodeService;

// Must be set before init so shutdown/layout middleware does not wrap this endpoint.
$noLayout = true;

require_once '../../src/includes/init.php';
require_once '../../src/classes/GlCodeService.php';

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
    respondJson(['success' => false, 'error' => 'Method not allowed'], 405);
}

$glCode = trim($_POST['gl_code'] ?? '');
$description = trim($_POST['description'] ?? '');
$accountType = trim($_POST['account_type'] ?? '');

if (empty($glCode) || empty($description) || empty($accountType)) {
    respondJson(['success' => false, 'error' => 'All fields are required'], 400);
}

$glCodeService = new GlCodeService($pdo);
$result = $glCodeService->updateGlCode($glCode, $description, $accountType);

respondJson($result);