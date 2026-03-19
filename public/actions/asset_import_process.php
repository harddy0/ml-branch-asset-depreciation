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
//  Returns JSON: { success, preview[], errors[], hasErrors, categories{} }
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
//  PHASE 2 — COMMIT (regular POST from the hidden confirm form)
//  User has reviewed the modal and clicked "Confirm Import".
// ══════════════════════════════════════════════════════════════════════
if (isset($_POST['action']) && $_POST['action'] === 'commit') {

    $parsed = $_SESSION['pending_import_data'] ?? null;

    if (!$parsed) {
        $_SESSION['flash_error'] = 'Session expired or no import data found. Please upload the file again.';
        header('Location: ' . BASE_URL . '/public/asset-import/');
        exit;
    }

    // Clean up session immediately — prevents double-submit
    unset($_SESSION['pending_import_data']);

    if (!$parsed['success']) {
        $_SESSION['flash_error'] = $parsed['error'];
        header('Location: ' . BASE_URL . '/public/asset-import/');
        exit;
    }

    // ── Resolve which row_nums the user actually selected ────────────
    // JS sends: selected_rows = JSON array of row_num strings e.g. ["2","4","7"]
    $selectedRaw  = $_POST['selected_rows'] ?? '';
    $selectedNums = [];
    if (!empty($selectedRaw)) {
        $decoded = json_decode($selectedRaw, true);
        if (is_array($decoded)) {
            // Normalise to strings for comparison (row_num is always a string or int)
            $selectedNums = array_map('strval', $decoded);
        }
    }

    // ── Apply any in-browser edits the user made ─────────────────────
    // JS sends: edited_rows = JSON array of full row objects that were modified
    $editedRaw  = $_POST['edited_rows'] ?? '';
    $editedMap  = []; // keyed by row_num string
    if (!empty($editedRaw)) {
        $decoded = json_decode($editedRaw, true);
        if (is_array($decoded)) {
            foreach ($decoded as $editedRow) {
                $rn = strval($editedRow['row_num'] ?? '');
                if ($rn !== '') {
                    $editedMap[$rn] = $editedRow;
                }
            }
        }
    }

    // ── Build the final list of rows to commit ────────────────────────
    // Rules:
    //   1. Only rows whose row_num is in $selectedNums
    //   2. Must not have has_error = true
    //   3. If the user edited a row (editedMap), use the edited version
    $rowsToCommit = [];
    foreach ($parsed['preview'] as $row) {
        $rn = strval($row['row_num'] ?? '');

        // Skip if not selected by the user
        if (!empty($selectedNums) && !in_array($rn, $selectedNums, true)) {
            continue;
        }

        // Skip error/duplicate rows — they can never be imported
        if (!empty($row['has_error'])) {
            continue;
        }

        // Merge in any edits the user made in the browser
        if (isset($editedMap[$rn])) {
            $edited = $editedMap[$rn];

            // Overwrite only the user-editable fields
            foreach (['reference_no', 'description', 'date_received',
                      'acquisition_cost', 'monthly_depreciation',
                      'category_name', 'category_code', 'depreciation_start'] as $field) {
                if (array_key_exists($field, $edited)) {
                    $row[$field] = $edited[$field];
                }
            }

            // ── Rebuild system_asset_code from the updated parts ──────
            // Format: {CATCODE}-{ZONE}-{BRANCHCODE}-{REFNO or random suffix}
            // branch_code is stored on the row from previewImport (never editable)
            $suffix = !empty($row['reference_no'])
                ? $row['reference_no']
                : strtoupper(substr(uniqid(), -5));
            $row['system_asset_code'] = sprintf(
                "%s-%s-%s-%s",
                $row['category_code'],
                $row['zone'],
                $row['branch_code'] ?? '',
                $suffix
            );
        }

        $rowsToCommit[] = $row;
    }

    if (empty($rowsToCommit)) {
        $_SESSION['flash_error'] = 'No valid rows were selected for import.';
        header('Location: ' . BASE_URL . '/public/asset-import/');
        exit;
    }

    $result = $importService->commitImport($rowsToCommit, (int)$_SESSION['user_id']);

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

// Fallback
$_SESSION['flash_error'] = 'Invalid request.';
header('Location: ' . BASE_URL . '/public/asset-import/');
exit;