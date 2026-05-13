<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/IssuanceStagingService.php';

while (ob_get_level()) { ob_end_clean(); }
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

try {
    $service = new \App\IssuanceStagingService($pdo);
    $result = $service->getStagingList($_GET);

    echo json_encode(array_merge(['success' => true], $result));
    exit;

} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    exit;
}
