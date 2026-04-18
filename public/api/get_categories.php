<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/AssetClassificationService.php';

ini_set('display_errors', '0');
error_reporting(0);
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method.'
    ]);
    exit;
}

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized.'
    ]);
    exit;
}

try {
    $service = new \App\AssetClassificationService($pdo);
    $rules = $service->getAllAmortizationRules();

    echo json_encode([
        'success' => true,
        'data' => $rules,
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to fetch categories.'
    ]);
}

exit;
