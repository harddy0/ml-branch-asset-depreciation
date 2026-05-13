<?php
$noLayout = true;

require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/IssuanceImportService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/issuance-import/');
    exit;
}

$importService = new \App\IssuanceImportService($pdo);

$isAjax = (
    !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) || (
    !empty($_SERVER['HTTP_ACCEPT'])
    && stripos((string)$_SERVER['HTTP_ACCEPT'], 'application/json') !== false
);

$respondJson = static function (array $payload, int $statusCode = 200): void {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
};

// ══════════════════════════════════════════════════════════════════════
//  PHASE 1 — PREVIEW (AJAX)
// ══════════════════════════════════════════════════════════════════════
if (isset($_POST['action']) && $_POST['action'] === 'preview') {
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        $respondJson(['success' => false, 'error' => 'Please upload a valid Excel file.']);
    }

    $fileTmp  = $_FILES['import_file']['tmp_name'];
    $fileName = $_FILES['import_file']['name'];
    $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
        $respondJson(['success' => false, 'error' => 'Invalid file type. Only .xlsx, .xls, and .csv are allowed.']);
    }

    $result = $importService->previewImport($fileTmp);

    if ($result['success']) {
        $_SESSION['pending_issuance_import_data'] = $result;
    }

    $respondJson($result);
}

// ══════════════════════════════════════════════════════════════════════
//  PHASE 2 — COMMIT
// ══════════════════════════════════════════════════════════════════════
if (isset($_POST['action']) && $_POST['action'] === 'commit') {
    $parsed = $_SESSION['pending_issuance_import_data'] ?? null;

    if (!$parsed) {
        $respondJson(['success' => false, 'error' => 'Session expired or no import data found. Please upload the file again.'], 400);
    }

    if (!$parsed['success']) {
        $respondJson(['success' => false, 'error' => (string)$parsed['error']], 400);
    }

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

    $result = $importService->prepareAndCommit($parsed['preview'], $selectedNums, $editedMap);

    if ($result['success']) {
        $msg = "Successfully imported {$result['count']} row(s).";
        if ($result['skipped'] > 0) {
            $msg .= " {$result['skipped']} duplicate(s) were skipped.";
        }
        if (!empty($result['errors'])) {
            $msg .= " " . count($result['errors']) . " error(s) occurred.";
        }
        
        $_SESSION['flash_success'] = $msg;

        unset($_SESSION['pending_issuance_import_data']);
        
        $respondJson([
            'success' => true,
            'count'   => (int)($result['count'] ?? 0),
            'skipped' => (int)($result['skipped'] ?? 0),
            'errors'  => $result['errors'] ?? [],
            'message' => $msg,
        ]);
    } else {
        $firstError = $result['errors'][0] ?? 'Import failed.';
        $respondJson(['success' => false, 'error' => (string)$firstError], 400);
    }
}

$respondJson(['success' => false, 'error' => 'Invalid request.'], 400);