<?php
namespace App;

class AssetService {
    private \PDO $db;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }

    /**
     * ==========================================
     * CREATE (Add New Asset Only)
     * ==========================================
     */
    public function createAsset(array $data, int $userId): array {
        try {
            $this->db->beginTransaction();

            $sql = "
                INSERT INTO assets (
                    system_asset_code, reference_no, 
                    main_zone_code, zone_code, region_code, cost_center_code, branch_name,
                    group_code, asset_code, depreciation_code, 
                    description, serial_number, quantity, property_type,
                    date_received, depreciation_start_date, depreciation_end_date,
                    depreciation_on, depreciation_day,
                    acquisition_cost, cost_unit, item_code, monthly_depreciation, status,
                    created_by
                ) VALUES (
                    :system_asset_code, :reference_no, 
                    :main_zone_code, :zone_code, :region_code, :cost_center_code, :branch_name,
                    :group_code, :asset_code, :depreciation_code, 
                    :description, :serial_number, :quantity, :property_type,
                    :date_received, :depreciation_start_date, :depreciation_end_date,
                    :depreciation_on, :depreciation_day, 
                    :acquisition_cost, :cost_unit, :item_code, :monthly_depreciation, :status,
                    :created_by
                )
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':system_asset_code'       => $data['system_asset_code'],
                ':reference_no'            => $data['reference_no'],
                ':main_zone_code'          => $data['main_zone_code'],
                ':zone_code'               => $data['zone_code'],
                ':region_code'             => $data['region_code'],
                ':cost_center_code'        => $data['cost_center_code'],
                ':branch_name'             => $data['branch_name'],
                ':group_code'              => $data['group_code'],
                ':asset_code'              => $data['asset_code'],
                ':depreciation_code'       => $data['depreciation_code'],
                ':description'             => $data['description'],
                ':serial_number'           => $data['serial_number'],
                ':quantity'                => $data['quantity'],
                ':property_type'           => $data['property_type'],
                ':date_received'           => $data['date_received'],
                ':depreciation_start_date' => $data['depreciation_start_date'],
                ':depreciation_end_date'   => $data['depreciation_end_date'],
                ':depreciation_on'         => $data['depreciation_on'],
                ':depreciation_day'        => $data['depreciation_day'],
                ':acquisition_cost'        => $data['acquisition_cost'],
                ':cost_unit'               => $data['cost_unit'],
                ':item_code'               => $data['item_code'],
                ':monthly_depreciation'    => $data['monthly_depreciation'],
                ':status'                  => $data['status'],
                ':created_by'              => $userId
            ]);

            $assetId = (int)$this->db->lastInsertId();

            $runningDepService = new RunningDepreciationService($this->db);
            $runningDepService->initializeForAsset(
                $assetId,
                (float)$data['acquisition_cost'],
                (string)$data['group_code']
            );

            $this->generateAssetLedgerEntries($assetId, $data);

            $this->db->commit();

            return ['success' => true, 'asset_id' => $assetId];

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * ==========================================
     * READ (Get Single Asset)
     * ==========================================
     */
    public function getAssetById(int $id): ?array {
        $sql = "SELECT * FROM assets WHERE id = :id LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * ==========================================
     * READ (Get Multiple / Paginated / Filtered)
     * ==========================================
     */
    public function getAssets(array $filters = [], int $limit = 50, int $offset = 0): array {
        $sql = "
            SELECT id, system_asset_code, description, branch_name, 
                   acquisition_cost, status, date_received 
            FROM assets 
            WHERE 1=1
        ";
        
        $params = [];

        // Apply dynamic filters
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['zone_code'])) {
            $sql .= " AND zone_code = :zone_code";
            $params[':zone_code'] = $filters['zone_code'];
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        
        // Bind parameters safely
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * ==========================================
     * READ (Depreciation List / Active Only)
     * ==========================================
     */
    public function getDepreciationList(array $options = []): array {
        $page = max(1, (int)($options['page'] ?? 1));
        $perPage = max(1, min(100, (int)($options['per_page'] ?? 50)));

        $search = trim((string)($options['search'] ?? ''));
        $groupCode = trim((string)($options['group_code'] ?? ''));
        $branchName = trim((string)($options['branch_name'] ?? ''));
        $dateFrom = trim((string)($options['date_from'] ?? ''));
        $dateTo = trim((string)($options['date_to'] ?? ''));
        $status = trim((string)($options['status'] ?? ''));
        $sortBy = (string)($options['sort_by'] ?? 'created_at');
        $sortDir = strtoupper((string)($options['sort_dir'] ?? 'DESC'));

        $sortMap = [
            'serial_number' => 'a.serial_number',
            'description' => 'a.description',
            'item_code' => 'a.item_code',
            'group_code' => 'a.group_code',
            'branch_name' => 'a.branch_name',
            'acquisition_cost' => 'a.acquisition_cost',
            'monthly_depreciation' => 'a.monthly_depreciation',
            'status' => 'a.status',
            'depreciation_end_date' => 'a.depreciation_end_date',
            'created_at' => 'a.created_at',
            'uploaded_by' => 'u.username',
        ];

        $safeSortColumn = $sortMap[$sortBy] ?? $sortMap['created_at'];
        $safeSortDir = ($sortDir === 'ASC') ? 'ASC' : 'DESC';

        $where = [];
        $params = [];

        if ($status !== '') {
            $where[] = 'a.status = :status';
            $params[':status'] = $status;
        } else {
            $where[] = "a.status = 'ACTIVE'";
        }

        if ($groupCode !== '') {
            $where[] = 'a.group_code = :group_code';
            $params[':group_code'] = $groupCode;
        }

        if ($branchName !== '') {
            $where[] = 'a.branch_name = :branch_name';
            $params[':branch_name'] = $branchName;
        }

        if ($dateFrom !== '') {
            $where[] = 'DATE(a.created_at) >= :date_from';
            $params[':date_from'] = $dateFrom;
        }

        if ($dateTo !== '') {
            $where[] = 'DATE(a.created_at) <= :date_to';
            $params[':date_to'] = $dateTo;
        }

        if ($search !== '') {
            $where[] = '(
                a.serial_number LIKE :search
                OR a.description LIKE :search
                OR a.item_code LIKE :search
                OR a.group_code LIKE :search
                OR a.branch_name LIKE :search
                OR u.username LIKE :search
            )';
            $params[':search'] = '%' . $search . '%';
        }

        $whereSql = implode(' AND ', $where);

        $countSql = "
            SELECT COUNT(*)
            FROM assets a
            LEFT JOIN users u ON u.id = a.created_by
            WHERE {$whereSql}
        ";

        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;

        $dataSql = "
            SELECT
                a.id,
                a.system_asset_code,
                a.serial_number,
                a.description,
                a.item_code,
                a.group_code,
                a.branch_name,
                a.acquisition_cost,
                a.monthly_depreciation,
                a.status,
                a.depreciation_end_date,
                a.created_at,
                u.username AS uploaded_by
            FROM assets a
            LEFT JOIN users u ON u.id = a.created_by
            WHERE {$whereSql}
            ORDER BY {$safeSortColumn} {$safeSortDir}, a.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $dataStmt = $this->db->prepare($dataSql);
        foreach ($params as $key => $value) {
            $dataStmt->bindValue($key, $value);
        }
        $dataStmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $dataStmt->execute();

        $rows = $dataStmt->fetchAll(\PDO::FETCH_ASSOC);

        $branchWhere = ["a.branch_name IS NOT NULL", "a.branch_name <> ''"];
        $branchParams = [];

        if ($status !== '') {
            $branchWhere[] = 'a.status = :status';
            $branchParams[':status'] = $status;
        } else {
            $branchWhere[] = "a.status = 'ACTIVE'";
        }

        if ($groupCode !== '') {
            $branchWhere[] = 'a.group_code = :group_code';
            $branchParams[':group_code'] = $groupCode;
        }

        if ($dateFrom !== '') {
            $branchWhere[] = 'DATE(a.created_at) >= :date_from';
            $branchParams[':date_from'] = $dateFrom;
        }

        if ($dateTo !== '') {
            $branchWhere[] = 'DATE(a.created_at) <= :date_to';
            $branchParams[':date_to'] = $dateTo;
        }

        $branchSql = '
            SELECT DISTINCT a.branch_name
            FROM assets a
            WHERE ' . implode(' AND ', $branchWhere) . '
            ORDER BY a.branch_name ASC
        ';

        $branchStmt = $this->db->prepare($branchSql);
        foreach ($branchParams as $key => $value) {
            $branchStmt->bindValue($key, $value);
        }
        $branchStmt->execute();
        $branches = $branchStmt->fetchAll(\PDO::FETCH_COLUMN);

        return [
            'data' => $rows,
            'branches' => $branches,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages,
            ],
            'sort' => [
                'sort_by' => array_key_exists($sortBy, $sortMap) ? $sortBy : 'created_at',
                'sort_dir' => $safeSortDir,
            ],
                'filters' => [
                'search' => $search,
                'group_code' => $groupCode,
                'branch_name' => $branchName,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'status' => $status,
            ],
        ];
    }

    /**
     * ==========================================
     * UPDATE (Edit Asset Info)
     * ==========================================
     */
    public function updateAsset(int $id, array $data): array {
        $sql = "
            UPDATE assets 
            SET 
                reference_no = :reference_no,
                description = :description,
                serial_number = :serial_number
            WHERE id = :id
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id'            => $id,
                ':reference_no'  => $data['reference_no'] ?? null,
                ':description'   => $data['description'],
                ':serial_number' => $data['serial_number'] ?? null,
            ]);

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * ==========================================
     * DELETE / STATUS UPDATE (Dispose/Sell)
     * ==========================================
     */
    public function changeStatus(int $id, string $newStatus, ?string $retirementDate = null): array {
        $allowedStatuses = ['ACTIVE', 'SOLD', 'DISPOSED', 'DEPRECIATED'];
        
        if (!in_array($newStatus, $allowedStatuses)) {
            return ['success' => false, 'error' => 'Invalid status provided.'];
        }

        $sql = "UPDATE assets SET status = :status";
        $params = [
            ':id' => $id, 
            ':status' => $newStatus
        ];

        // If the asset is sold or disposed of, we log the exact date it left the company
        if (in_array($newStatus, ['SOLD', 'DISPOSED']) && $retirementDate) {
            $sql .= ", retirement_date = :retirement_date";
            $params[':retirement_date'] = $retirementDate;
        }

        $sql .= " WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generates the fully denormalized GL ledger entries for the entire lifespan of the asset.
     */
    private function generateAssetLedgerEntries(int $assetId, array $data): void {
        // 1. Fetch the total lifespan months from the asset_groups table
        $stmt = $this->db->prepare('SELECT actual_months FROM asset_groups WHERE group_code = :group_code LIMIT 1');
        $stmt->execute([':group_code' => $data['group_code']]);
        $lifespanMonths = (int)($stmt->fetchColumn() ?: 0);

        if ($lifespanMonths <= 0) {
            return; // Safety check
        }

        $acquisitionCost = (float)$data['acquisition_cost'];
        $monthlyDepreciation = (float)$data['monthly_depreciation'];
        $startDate = new \DateTime($data['depreciation_start_date']);

        // 2. Prepare the exact statement matching your depreciation_ledger table
        $sql = "
            INSERT INTO depreciation_ledger (
                asset_id, system_asset_code, description,
                main_zone_code, zone_code, region_code, cost_center_code, branch_name,
                group_code, asset_code, depreciation_code, property_type,
                acquisition_cost, monthly_depreciation,
                period_date, period_month, period_year,
                periods_elapsed, periods_remaining, period_depreciation_expense,
                accumulated_depreciation, book_value,
                gl_debit_code, gl_debit_amount, gl_credit_code, gl_credit_amount
            ) VALUES (
                :asset_id, :system_asset_code, :description,
                :main_zone_code, :zone_code, :region_code, :cost_center_code, :branch_name,
                :group_code, :asset_code, :depreciation_code, :property_type,
                :acquisition_cost, :monthly_depreciation,
                :period_date, :period_month, :period_year,
                :periods_elapsed, :periods_remaining, :period_depreciation_expense,
                :accumulated_depreciation, :book_value,
                :gl_debit_code, :gl_debit_amount, :gl_credit_code, :gl_credit_amount
            )
        ";
        
        $insertStmt = $this->db->prepare($sql);

        $accumulatedDepreciation = 0.00;

        // 3. Loop through every month and compute the running values
        for ($i = 0; $i < $lifespanMonths; $i++) {
            $currentPeriodDate = clone $startDate;
            $currentPeriodDate->modify("+{$i} months");
            
            $periodsElapsed = $i + 1;
            $periodsRemaining = $lifespanMonths - $periodsElapsed;
            $accumulatedDepreciation += $monthlyDepreciation;
            $bookValue = $acquisitionCost - $accumulatedDepreciation;
            
            // Adjust final month rounding to ensure book value hits exactly 0.00
            if ($periodsRemaining === 0) {
                $accumulatedDepreciation = $acquisitionCost;
                $bookValue = 0.00;
            }

            $insertStmt->execute([
                // Identity Snapshot
                ':asset_id'                   => $assetId,
                ':system_asset_code'          => $data['system_asset_code'],
                ':description'                => $data['description'],
                
                // Location Snapshot
                ':main_zone_code'             => $data['main_zone_code'],
                ':zone_code'                  => $data['zone_code'],
                ':region_code'                => $data['region_code'],
                ':cost_center_code'           => $data['cost_center_code'],
                ':branch_name'                => $data['branch_name'],
                
                // Classification Snapshot
                ':group_code'                 => $data['group_code'],
                ':asset_code'                 => $data['asset_code'],
                ':depreciation_code'          => $data['depreciation_code'],
                ':property_type'              => $data['property_type'],
                
                // Financial Snapshot
                ':acquisition_cost'           => $acquisitionCost,
                ':monthly_depreciation'       => $monthlyDepreciation,
                
                // Period Info
                ':period_date'                => $currentPeriodDate->format('Y-m-d'),
                ':period_month'               => (int)$currentPeriodDate->format('n'),
                ':period_year'                => (int)$currentPeriodDate->format('Y'),
                
                // Computed Values at This Period
                ':periods_elapsed'            => $periodsElapsed,
                ':periods_remaining'          => $periodsRemaining,
                ':period_depreciation_expense'=> $monthlyDepreciation,
                ':accumulated_depreciation'   => round($accumulatedDepreciation, 2),
                ':book_value'                 => round($bookValue, 2),
                
                // Journal Entry (GL Posting)
                ':gl_debit_code'              => $data['depreciation_code'],
                ':gl_debit_amount'            => $monthlyDepreciation,
                ':gl_credit_code'             => $data['asset_code'],
                ':gl_credit_amount'           => $monthlyDepreciation
            ]);
        }
    }

}