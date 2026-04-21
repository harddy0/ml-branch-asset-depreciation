<?php
require_once __DIR__ . '/src/includes/init.php';

// Query to show depreciation ledger rows grouped by asset
$sql = '
    SELECT 
        asset_id, 
        system_asset_code,
        COUNT(*) as row_count,
        MIN(period_date) as first_date,
        MAX(period_date) as last_date,
        asset_name
    FROM depreciation_ledger
    GROUP BY asset_id, system_asset_code, asset_name
    ORDER BY row_count DESC
    LIMIT 10
';

$stmt = $pdo->prepare($sql);
$stmt->execute();
$results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

echo "=== DEPRECIATION LEDGER SUMMARY ===\n\n";
echo "Total assets with ledger entries: " . count($results) . "\n\n";

foreach ($results as $asset) {
    echo "Asset ID: " . $asset['asset_id'] . "\n";
    echo "  Code: " . $asset['system_asset_code'] . "\n";
    echo "  Name: " . $asset['asset_name'] . "\n";
    echo "  Row Count: " . $asset['row_count'] . "\n";
    echo "  Date Range: " . $asset['first_date'] . " to " . $asset['last_date'] . "\n";
    echo "\n";
}

// Now show detailed data for the most recent asset (highest row count)
if (count($results) > 0) {
    $topAsset = $results[0];
    
    echo "\n=== DETAILED ROWS FOR ASSET ID: " . $topAsset['asset_id'] . " ===\n\n";
    
    $detailSql = '
        SELECT 
            id,
            period_date,
            gl_a_code,
            gl_a_type,
            gl_a_amount,
            gl_b_code,
            gl_b_type,
            gl_b_amount,
            accumulated_depreciation,
            book_value,
            created_at
        FROM depreciation_ledger
        WHERE asset_id = ?
        ORDER BY period_date ASC
    ';
    
    $detailStmt = $pdo->prepare($detailSql);
    $detailStmt->execute([$topAsset['asset_id']]);
    $detailRows = $detailStmt->fetchAll(\PDO::FETCH_ASSOC);
    
    foreach ($detailRows as $idx => $row) {
        echo "Row " . ($idx + 1) . ":\n";
        echo "  Period Date: " . $row['period_date'] . "\n";
        echo "  GL A: " . $row['gl_a_code'] . " (" . $row['gl_a_type'] . ") = " . $row['gl_a_amount'] . "\n";
        echo "  GL B: " . $row['gl_b_code'] . " (" . $row['gl_b_type'] . ") = " . $row['gl_b_amount'] . "\n";
        echo "  Accumulated: " . $row['accumulated_depreciation'] . " | Book Value: " . $row['book_value'] . "\n";
        echo "  Created: " . $row['created_at'] . "\n";
        echo "\n";
    }
}
?>
