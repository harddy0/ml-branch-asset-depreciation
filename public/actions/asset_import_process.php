<?php
// Must be set BEFORE init.php so the shutdown-function layout wrapper
// never fires — this file serves both JSON (AJAX preview) and redirects (commit).
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
//  Triggered by JS before showing the review modal.
//  Returns JSON: { success, preview[], errors[], hasErrors }
// ══════════════════════════════════════════════════════════════════════
if (isset($_POST['action']) && $_POST['action'] === 'preview') {

    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Please upload a valid Excel file.']);
        exit;
    }

    // Read directly from PHP's temporary uploaded file path
    $fileTmp  = $_FILES['import_file']['tmp_name'];
    $fileName = $_FILES['import_file']['name'];
    $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only .xlsx, .xls, and .csv are allowed.']);
        exit;
    }

    // Parse the data directly from the temporary file
    $result = $importService->previewImport($fileTmp);

    // Save the parsed JSON results into the session so we don't have to re-read the file
    if ($result['success']) {
        $_SESSION['pending_import_data'] = $result;
    }

    // Strip any BOM or leaked whitespace before sending JSON
    while (ob_get_level() > 0) ob_end_clean();

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result);
    exit;
}

// ══════════════════════════════════════════════════════════════════════
//  PHASE 2 — COMMIT (regular POST from the hidden confirm form)
//  User has reviewed the modal and clicked "Confirm Import".
// ══════════════════════════════════════════════════════════════════════
if (isset($_POST['action']) && $_POST['action'] === 'commit') {
    
    // Retrieve the parsed data from the session
    $parsed = $_SESSION['pending_import_data'] ?? null;

    if (!$parsed) {
        $_SESSION['flash_error'] = 'Session expired or no import data found. Please upload the file again.';
        header('Location: ' . BASE_URL . '/public/asset-import/');
        exit;
    }

    // Clean up the session data immediately so it cannot be double-submitted
    unset($_SESSION['pending_import_data']);

    if (!$parsed['success']) {
        $_SESSION['flash_error'] = $parsed['error'];
        header('Location: ' . BASE_URL . '/public/asset-import/');
        exit;
    }

    if ($parsed['hasErrors']) {
        $_SESSION['flash_error']   = 'Import rejected. Please fix the validation errors and try again.';
        $_SESSION['import_errors'] = $parsed['errors'];
        header('Location: ' . BASE_URL . '/public/asset-import/');
        exit;
    }

    // Only commit the clean rows
    $cleanRows = array_filter($parsed['preview'], fn($r) => empty($r['has_error']));
    $result    = $importService->commitImport(array_values($cleanRows), (int)$_SESSION['user_id']);

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

// Fallback for direct POSTs without action param (legacy)
$_SESSION['flash_error'] = 'Invalid request.';
header('Location: ' . BASE_URL . '/public/asset-import/');
exit;