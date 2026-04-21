<?php
require_once __DIR__ . '/src/includes/init.php';
require_once __DIR__ . '/src/classes/LedgerReportService.php';

// Test the API by calling the service directly
$service = new \App\LedgerReportService($pdo);

// Get ledger report for asset 1 with no filters
$report = $service->getAssetLedgerReport(1, [
    'entry_side' => 'ALL'
]);

echo "\n=== API RESPONSE STRUCTURE ===\n\n";
echo "Asset ID: " . $report['asset']['id'] . "\n";
echo "Asset Code: " . $report['asset']['system_asset_code'] . "\n";
echo "Total ledger_rows returned: " . count($report['ledger_rows']) . "\n";
echo "Total fs_rows returned: " . count($report['fs_rows']) . "\n";
echo "Row count in totals: " . $report['totals']['row_count'] . "\n";
echo "\n";

echo "=== FIRST 3 LEDGER ROWS FROM API ===\n\n";
for ($i = 0; $i < 3 && $i < count($report['ledger_rows']); $i++) {
    $row = $report['ledger_rows'][$i];
    echo "Ledger Row " . ($i+1) . ":\n";
    echo "  Period: " . $row['period_date'] . "\n";
    echo "  GL Debit Code: " . ($row['gl_debit_code'] ?: "EMPTY") . " = " . $row['gl_debit_amount'] . "\n";
    echo "  GL Debit Desc: " . ($row['gl_debit_description'] ?: "EMPTY") . "\n";
    echo "  GL Credit Code: " . ($row['gl_credit_code'] ?: "EMPTY") . " = " . $row['gl_credit_amount'] . "\n";
    echo "  GL Credit Desc: " . ($row['gl_credit_description'] ?: "EMPTY") . "\n";
    echo "\n";
}

echo "=== FIRST 6 FS_ROWS FROM API ===\n\n";
for ($i = 0; $i < 6 && $i < count($report['fs_rows']); $i++) {
    $row = $report['fs_rows'][$i];
    echo "FS Row " . ($i+1) . ":\n";
    echo "  Ledger ID: " . $row['ledger_id'] . "\n";
    echo "  Entry Side: " . $row['entry_side'] . "\n";
    echo "  Account: " . $row['account_code'] . " - " . $row['account_name'] . "\n";
    echo "  Debit: " . $row['debit_amount'] . " | Credit: " . $row['credit_amount'] . "\n";
    echo "\n";
}
?>
