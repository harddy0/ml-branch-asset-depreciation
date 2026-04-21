<?php
require_once __DIR__ . '/src/includes/init.php';
require_once __DIR__ . '/src/classes/LedgerReportService.php';

// Test the complete rendering flow
$service = new \App\LedgerReportService($pdo);

// Test different filter scenarios
$filters_tests = [
    ['entry_side' => 'ALL', 'name' => 'ALL (should show all)'],
    ['entry_side' => 'DEBIT', 'name' => 'DEBIT Only'],
    ['entry_side' => 'CREDIT', 'name' => 'CREDIT Only'],
];

echo "\n=== TESTING ALL FILTER SCENARIOS ===\n\n";

foreach ($filters_tests as $test) {
    echo "Filter: " . $test['name'] . "\n";
    echo str_repeat("-", 50) . "\n";
    
    $report = $service->getAssetLedgerReport(1, ['entry_side' => $test['entry_side']]);
    
    echo "API Response:\n";
    echo "  ledger_rows count: " . count($report['ledger_rows']) . "\n";
    echo "  fs_rows count: " . count($report['fs_rows']) . "\n";
    
    // Simulate getLedgerLineRows() from JS
    $lineRows = [];
    foreach ($report['ledger_rows'] as $r) {
        if ($r['gl_debit_code']) {
            $lineRows[] = ['type' => 'DEBIT', 'code' => $r['gl_debit_code']];
        }
        if ($r['gl_credit_code']) {
            $lineRows[] = ['type' => 'CREDIT', 'code' => $r['gl_credit_code']];
        }
    }
    
    echo "Ledger Tab (after getLedgerLineRows): " . count($lineRows) . " rows would render\n";
    
    // Count FS rows with gl codes
    $fsRowsRendered = 0;
    foreach ($report['ledger_rows'] as $r) {
        if ($r['gl_debit_code']) $fsRowsRendered++;
        if ($r['gl_credit_code']) $fsRowsRendered++;
    }
    
    echo "FS Tab: " . $fsRowsRendered . " rows would render\n";
    
    echo "\nFirst row in ledger_rows:\n";
    if (count($report['ledger_rows']) > 0) {
        $r = $report['ledger_rows'][0];
        echo "  gl_debit_code: " . ($r['gl_debit_code'] ?: "EMPTY") . "\n";
        echo "  gl_credit_code: " . ($r['gl_credit_code'] ?: "EMPTY") . "\n";
    }
    
    echo "\n";
}

echo "\n=== ANALYSIS ===\n";
echo "If user sees 'only 1 row', possibilities:\n";
echo "1. Entry side filter is set to DEBIT or CREDIT → Shows 12 lines (1 per ledger entry)\n";
echo "2. Only first row is visible → Scroll the table to see more\n";
echo "3. Tab not switching → Click FS tab to see different view\n";
echo "4. Browser cache issue → Refresh page\n";
?>
