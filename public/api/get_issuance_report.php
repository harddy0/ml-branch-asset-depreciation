<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';

while (ob_get_level()) { ob_end_clean(); }
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Get pagination parameters
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 50)));
    $offset = ($page - 1) * $perPage;
    
    // Get filter parameters
    $search = trim($_GET['search'] ?? '');
    $zone = trim($_GET['zone'] ?? '');
    $region = trim($_GET['region'] ?? '');
    $branchName = trim($_GET['branch_name'] ?? '');
    $productCategory = trim($_GET['product_category'] ?? '');
    $dateFrom = trim($_GET['date_from'] ?? '');
    $dateTo = trim($_GET['date_to'] ?? '');
    
    // Get sort parameters
    $sortBy = $_GET['sort_by'] ?? 'date_issued';
    $sortDir = strtoupper($_GET['sort_dir'] ?? 'DESC');
    
    // Validate sort column (whitelist to prevent SQL injection)
    $allowedSortColumns = ['date_issued', 'issuance_number', 'quantity', 'unit_cost', 'total_amount', 'product_category', 'zone', 'region', 'branch_name', 'created_at'];
    if (!in_array($sortBy, $allowedSortColumns)) {
        $sortBy = 'date_issued';
    }
    $sortDir = ($sortDir === 'ASC') ? 'ASC' : 'DESC';
    
    // Build WHERE clause
    $whereConditions = [];
    $params = [];
    
    if ($search !== '') {
        $whereConditions[] = "(issuance_number LIKE :search OR item_description LIKE :search OR cost_center_raw LIKE :search)";
        $params[':search'] = "%{$search}%";
    }
    
    if ($zone !== '') {
        $whereConditions[] = "zone = :zone";
        $params[':zone'] = $zone;
    }
    
    if ($region !== '') {
        $whereConditions[] = "region = :region";
        $params[':region'] = $region;
    }
    
    if ($branchName !== '') {
        $whereConditions[] = "branch_name = :branch_name";
        $params[':branch_name'] = $branchName;
    }
    
    if ($productCategory !== '') {
        $whereConditions[] = "product_category = :product_category";
        $params[':product_category'] = $productCategory;
    }
    
    if ($dateFrom !== '') {
        $whereConditions[] = "DATE(date_issued) >= :date_from";
        $params[':date_from'] = $dateFrom;
    }
    
    if ($dateTo !== '') {
        $whereConditions[] = "DATE(date_issued) <= :date_to";
        $params[':date_to'] = $dateTo;
    }
    
    $whereSql = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);
    
    // Get total count (ONE query for pagination)
    $countSql = "SELECT COUNT(*) as total FROM issuance_staging {$whereSql}";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();
    $totalPages = ceil($total / $perPage);
    
    // Get data for current page (single query with proper pagination)
    $dataSql = "
        SELECT 
            id,
            DATE(date_issued) as date_issued,
            issuance_number,
            item_code,
            item_description,
            quantity,
            uom,
            cost_center_raw,
            unit_cost,
            total_amount,
            description_remarks,
            product_category,
            zone,
            region,
            branch_name,
            source_status,
            transfer_status,
            DATE(created_at) as created_at
        FROM issuance_staging
        {$whereSql}
        ORDER BY {$sortBy} {$sortDir}
        LIMIT :limit OFFSET :offset
    ";
    
    $dataStmt = $pdo->prepare($dataSql);
    foreach ($params as $key => $value) {
        $dataStmt->bindValue($key, $value);
    }
    $dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $dataStmt->execute();
    $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get totals for filtered data (ONE query for summary)
    $totalsSql = "
        SELECT 
            COALESCE(SUM(total_amount), 0) as total_amount,
            COALESCE(SUM(quantity), 0) as total_quantity
        FROM issuance_staging
        {$whereSql}
    ";
    $totalsStmt = $pdo->prepare($totalsSql);
    foreach ($params as $key => $value) {
        $totalsStmt->bindValue($key, $value);
    }
    $totalsStmt->execute();
    $totals = $totalsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get filter options (only when needed, cached)
    $options = [];
    if (empty($_GET['page']) || $_GET['page'] == 1) {
        // Only fetch options on first page or when explicitly requested
        $options['zones'] = $pdo->query("SELECT DISTINCT zone FROM issuance_staging WHERE zone IS NOT NULL AND zone != '' ORDER BY zone ASC")->fetchAll(PDO::FETCH_COLUMN);
        $options['regions'] = $pdo->query("SELECT DISTINCT region FROM issuance_staging WHERE region IS NOT NULL AND region != '' ORDER BY region ASC")->fetchAll(PDO::FETCH_COLUMN);
        $options['branches'] = $pdo->query("SELECT DISTINCT branch_name FROM issuance_staging WHERE branch_name IS NOT NULL AND branch_name != '' ORDER BY branch_name ASC")->fetchAll(PDO::FETCH_COLUMN);
        $options['categories'] = $pdo->query("SELECT DISTINCT product_category FROM issuance_staging WHERE product_category IS NOT NULL AND product_category != '' ORDER BY product_category ASC")->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
        ],
        'totals' => [
            'total_amount' => (float)($totals['total_amount'] ?? 0),
            'total_quantity' => (int)($totals['total_quantity'] ?? 0),
        ],
        'filters' => [
            'search' => $search,
            'zone' => $zone,
            'region' => $region,
            'branch_name' => $branchName,
            'product_category' => $productCategory,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ],
        'options' => $options,
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}