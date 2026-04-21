<?php
require_once __DIR__ . '/src/includes/init.php';

// Simple query to get ledger data with GL descriptions via join
$sql = '
    SELECT 
        l.id,
        l.asset_id,
        l.system_asset_code,
        l.period_date,
        l.gl_a_code,
        l.gl_a_type,
        l.gl_a_amount,
        l.gl_b_code,
        l.gl_b_type,
        l.gl_b_amount,
        COALESCE(ga.description, "NO DESC") as ga_desc,
        COALESCE(gb.description, "NO DESC") as gb_desc
    FROM depreciation_ledger l
    LEFT JOIN gl_codes ga ON ga.gl_code = l.gl_a_code
    LEFT JOIN gl_codes gb ON gb.gl_code = l.gl_b_code
    WHERE l.asset_id = 1
    ORDER BY l.period_date ASC
    LIMIT 5
';

try {
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    echo "\n=== DATABASE LEDGER RECORDS FOR ASSET 1 (First 5) ===\n\n";
    
    foreach ($rows as $idx => $row) {
        echo "Record " . ($idx + 1) . ":\n";
        echo "  Period: " . $row['period_date'] . "\n";
        echo "  GL A: " . $row['gl_a_code'] . " (" . $row['gl_a_type'] . ") = " . $row['gl_a_amount'] . " [" . $row['ga_desc'] . "]\n";
        echo "  GL B: " . $row['gl_b_code'] . " (" . $row['gl_b_type'] . ") = " . $row['gl_b_amount'] . " [" . $row['gb_desc'] . "]\n";
        echo "\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
