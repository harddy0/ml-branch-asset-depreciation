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

    $fileTmp  = $_FILES['import_file']['tmp_name'];
    $fileName = $_FILES['import_file']['name'];
    $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only .xlsx, .xls, and .csv are allowed.']);
        exit;
    }

    // Move to storage so it survives the AJAX round-trip for the commit phase
    $storedName = 'import_' . session_id() . '_' . time() . '.' . $ext;
    $storedPath = __DIR__ . '/../../storage/uploads/' . $storedName;

    if (!move_uploaded_file($fileTmp, $storedPath)) {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Failed to store the uploaded file. Check storage/uploads permissions.']);
        exit;
    }

    // Keep the stored filename in session for the commit step
    $_SESSION['pending_import_file'] = $storedName;

    $result = $importService->previewImport($storedPath);

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
    $storedName = $_SESSION['pending_import_file'] ?? null;

    if (!$storedName) {
        $_SESSION['flash_error'] = 'Session expired. Please upload the file again.';
        header('Location: ' . BASE_URL . '/public/asset-import/');
        exit;
    }

    $storedPath = __DIR__ . '/../../storage/uploads/' . $storedName;

    if (!file_exists($storedPath)) {
        $_SESSION['flash_error'] = 'Uploaded file not found. Please upload again.';
        header('Location: ' . BASE_URL . '/public/asset-import/');
        exit;
    }

    // Re-parse (source of truth stays the file, not user-supplied POST data)
    $parsed = $importService->previewImport($storedPath);

    // Clean up stored file regardless of outcome
    @unlink($storedPath);
    unset($_SESSION['pending_import_file']);

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
    $cleanRows = array_filter($parsed['preview'], fn($r) => !$r['has_error']);
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