<?php
// Query GL type configuration from database

require_once __DIR__ . '/src/config/config.php';

try {
    // Connect to primary database
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "=== ASSET_GROUPS GL TYPE CONFIGURATION ===\n\n";
    
    // Query 1: Asset groups GL type configuration
    $query1 = "SELECT id, group_name, asset_gl_code, asset_gl_type, expense_gl_code, expense_gl_type 
               FROM asset_groups 
               LIMIT 5";
    
    $stmt = $pdo->query($query1);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Query 1: Asset Groups GL Type Configuration (First 5):\n";
    echo str_repeat("-", 120) . "\n";
    
    if (empty($results)) {
        echo "No asset groups found\n";
    } else {
        foreach ($results as $row) {
            echo sprintf(
                "ID: %d | Group: %-30s | Asset GL: %s (%s) | Expense GL: %s (%s)\n",
                $row['id'],
                $row['group_name'],
                $row['asset_gl_code'],
                $row['asset_gl_type'],
                $row['expense_gl_code'],
                $row['expense_gl_type']
            );
        }
    }
    
    echo "\n=== UNIQUE GL TYPE VALUES ===\n";
    $query_types = "SELECT DISTINCT asset_gl_type, expense_gl_type FROM asset_groups";
    $stmt = $pdo->query($query_types);
    $type_combinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($type_combinations as $combo) {
        echo sprintf("- Asset Type: %s | Expense Type: %s\n", 
                     $combo['asset_gl_type'], 
                     $combo['expense_gl_type']);
    }
    
    echo "\n=== DEPRECIATION_LEDGER VS ASSET_GROUPS CONFIGURATION ===\n\n";
    
    // Query 2: Get first asset and check depreciation ledger
    $query2 = "SELECT id FROM assets LIMIT 1";
    $stmt = $pdo->query($query2);
    $asset = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($asset) {
        $test_asset_id = $asset['id'];
        echo "Using test asset ID: $test_asset_id\n\n";
        
        $query3 = "SELECT ag.asset_gl_code, ag.asset_gl_type, ag.expense_gl_code, ag.expense_gl_type,
                          dl.gl_a_code, dl.gl_a_type, dl.gl_a_amount, dl.gl_b_code, dl.gl_b_type, dl.gl_b_amount
                   FROM depreciation_ledger dl
                   JOIN asset_groups ag ON ag.id = dl.asset_group_id
                   WHERE dl.asset_id = $test_asset_id
                   LIMIT 1";
        
        $stmt = $pdo->query($query3);
        $ledger_row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ledger_row) {
            echo "Depreciation Ledger Entry Analysis:\n";
            echo str_repeat("-", 100) . "\n";
            echo "\nConfiguration (from asset_groups):\n";
            echo sprintf("  Asset GL: %s (Type: %s)\n", $ledger_row['asset_gl_code'], $ledger_row['asset_gl_type']);
            echo sprintf("  Expense GL: %s (Type: %s)\n", $ledger_row['expense_gl_code'], $ledger_row['expense_gl_type']);
            
            echo "\nStored in Depreciation Ledger:\n";
            echo sprintf("  GL_A: %s (Type: %s, Amount: %s)\n", 
                        $ledger_row['gl_a_code'], 
                        $ledger_row['gl_a_type'], 
                        $ledger_row['gl_a_amount']);
            echo sprintf("  GL_B: %s (Type: %s, Amount: %s)\n", 
                        $ledger_row['gl_b_code'], 
                        $ledger_row['gl_b_type'], 
                        $ledger_row['gl_b_amount']);
            
            echo "\n" . str_repeat("-", 100) . "\n";
            echo "\nAnalysis:\n";
            echo "Configuration mismatch check:\n";
            if ($ledger_row['asset_gl_type'] === $ledger_row['gl_a_type']) {
                echo "  ✓ GL_A type matches asset_gl_type (CORRECT)\n";
            } else {
                echo "  ✗ GL_A type MISMATCH: Config=" . $ledger_row['asset_gl_type'] . ", Stored=" . $ledger_row['gl_a_type'] . "\n";
            }
            
            if ($ledger_row['expense_gl_type'] === $ledger_row['gl_b_type']) {
                echo "  ✓ GL_B type matches expense_gl_type (CORRECT)\n";
            } else {
                echo "  ✗ GL_B type MISMATCH: Config=" . $ledger_row['expense_gl_type'] . ", Stored=" . $ledger_row['gl_b_type'] . "\n";
            }
            
            echo "\nGL Type configuration (asset_groups):\n";
            if ($ledger_row['asset_gl_type'] === $ledger_row['expense_gl_type']) {
                echo "  WARNING: Both types are the same (" . $ledger_row['asset_gl_type'] . ")\n";
                echo "  This would cause both GL entries to show as DEBIT or both as CREDIT!\n";
            } else {
                echo "  OK: Asset type is " . $ledger_row['asset_gl_type'] . ", Expense type is " . $ledger_row['expense_gl_type'] . "\n";
            }
        } else {
            echo "No depreciation ledger entries found for asset ID $test_asset_id\n";
        }
    } else {
        echo "No assets found in database\n";
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
