<?php
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/ImportService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/asset-import/');
    exit;
}

if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash_error'] = 'Please upload a valid Excel file.';
    header('Location: ' . BASE_URL . '/public/asset-import/');
    exit;
}

$fileTmp = $_FILES['import_file']['tmp_name'];
$fileName = $_FILES['import_file']['name'];
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
    $_SESSION['flash_error'] = 'Invalid file type. Only .xlsx, .xls, and .csv are allowed.';
    header('Location: ' . BASE_URL . '/public/asset-import/');
    exit;
}

$importService = new \App\ImportService($pdo, $pdo2);
$result = $importService->processImport($fileTmp, (int)$_SESSION['user_id']);

if ($result['success']) {
    $_SESSION['flash_success'] = "Successfully imported {$result['count']} assets.";
} else {
    $_SESSION['flash_error'] = $result['error']; // The "Import rejected..." message
    if (!empty($result['errors'])) {
        // Send the specific row errors to the frontend
        $_SESSION['import_errors'] = $result['errors']; 
    }
}

header('Location: ' . BASE_URL . '/public/asset-import/');
exit;