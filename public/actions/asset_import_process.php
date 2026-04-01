<?php
$noLayout = true;

require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/ImportService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/asset-import/');
    exit;
}

$importService = new \App\ImportService($pdo, $pdo2);

// ══════════════════════════════════════════════════════════════════════
//  PHASE 1 — PREVIEW (AJAX)
// ══════════════════════════════════════════════════════════════════════
if (isset($_POST['action']) && $_POST['action'] === 'preview') {
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Please upload a valid Excel file.']);
        exit;
    }

    $fileTmp  = $_FILES['import_file']['tmp_name'];
    $fileName = $_FILES['import_file']['name'];
    $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only .xlsx, .xls, and .csv are allowed.']);
        exit;
    }

    $result = $importService->previewImport($fileTmp);

    if ($result['success']) {
        $_SESSION['pending_import_data'] = $result;
    }

    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result);
    exit;
}

// ══════════════════════════════════════════════════════════════════════
//  PHASE 2 — COMMIT (regular POST)
// ══════════════════════════════════════════════════════════════════════
if (isset($_POST['action']) && $_POST['action'] === 'commit') {
    $parsed = $_SESSION['pending_import_data'] ?? null;

    if (!$parsed) {
        $_SESSION['flash_error'] = 'Session expired or no import data found. Please upload the file again.';
        header('Location: ' . BASE_URL . '/public/asset-import/');
        exit;
    }

    unset($_SESSION['pending_import_data']);

    if (!$parsed['success']) {
        $_SESSION['flash_error'] = $parsed['error'];
        header('Location: ' . BASE_URL . '/public/asset-import/');
        exit;
    }

    // Decode user selections and edits
    $selectedNums = [];
    if (!empty($_POST['selected_rows'])) {
        $decoded = json_decode($_POST['selected_rows'], true);
        if (is_array($decoded)) $selectedNums = array_map('strval', $decoded);
    }

    $editedMap = [];
    if (!empty($_POST['edited_rows'])) {
        $decoded = json_decode($_POST['edited_rows'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $editedRow) {
                $rn = strval($editedRow['row_num'] ?? '');
                if ($rn !== '') $editedMap[$rn] = $editedRow;
            }
        }
    }

    // Delegate business logic to Service
    $result = $importService->prepareAndCommit($parsed['preview'], $selectedNums, $editedMap, (int)$_SESSION['user_id']);

    if ($result['success']) {
        $msg = "Successfully imported {$result['count']} asset(s).";
        if (!empty($result['skipped']) && $result['skipped'] > 0) {
            $msg .= " {$result['skipped']} duplicate(s) were skipped.";
        }
        $_SESSION['flash_success'] = $msg;
    } else {
        $_SESSION['flash_error'] = $result['error'];
    }

    header('Location: ' . BASE_URL . '/public/asset-import/');
    exit;
}

$_SESSION['flash_error'] = 'Invalid request.';
header('Location: ' . BASE_URL . '/public/asset-import/');
exit;