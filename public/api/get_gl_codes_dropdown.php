<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/GlCodeService.php'; // <-- ADDED: Manually load the class

header('Content-Type: application/json');

try {
    // FIXED: Changed $db to $pdo to match your init.php
    $glCodeService = new \App\GlCodeService($pdo);
    $data = $glCodeService->getAllForDropdown();
    
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;