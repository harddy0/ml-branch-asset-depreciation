<?php
namespace App;

class AssetService {
                /**
                 * Retrieves asset details (with depreciation and running totals) by system asset code.
                 * @param string $code System asset code
                 * @return array|null Asset details row or null if not found
                 */
                public function getAssetDetailsByCode(string $code): ?array {
                    $sql = "SELECT a.*, 
                                   ad.description AS pl_description,
                                   ad.description AS depreciation_label,
                                   ad.months AS policy_months,
                                   a.months AS asset_life_months,
                                   rd.accumulated_depreciation,
                                   rd.book_value,
                                   rd.last_depreciation_date AS period_date
                            FROM assets a
                            LEFT JOIN amortization_depreciation ad ON a.depreciation_code = ad.depreciation_code
                            LEFT JOIN running_depreciation rd ON a.id = rd.asset_id
                            WHERE a.system_asset_code = :code
                            LIMIT 1";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([':code' => $code]);
                    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                    return $row ?: null;
                }
            /**
             * Retrieves asset details (with depreciation and running totals) by asset ID.
             * @param int $id Asset ID
             * @return array|null Asset details row or null if not found
             */
            public function getAssetDetailsById(int $id): ?array {
                $sql = "SELECT a.*, 
                               ad.description AS pl_description,
                               ad.description AS depreciation_label,
                               ad.months AS policy_months,
                               a.months AS asset_life_months,
                               rd.accumulated_depreciation,
                               rd.book_value,
                               rd.last_depreciation_date AS period_date
                        FROM assets a
                        LEFT JOIN amortization_depreciation ad ON a.depreciation_code = ad.depreciation_code
                        LEFT JOIN running_depreciation rd ON a.id = rd.asset_id
                        WHERE a.id = :id
                        LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':id' => $id]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                return $row ?: null;
            }
        /**
         * Handles asset creation from sanitized POST data.
         * Moves all business logic from asset_store.php here.
         * @param array $postData Sanitized POST data
         * @param int $userId User ID
         * @return array Result array (success, error, asset_id, etc.)
         */
        public function createAssetFromRequest(array $postData, int $userId): array {
            try {
                // Helper: normalize a numeric string to a float-friendly format
                $normalizeNumber = function ($v) {
                    $s = trim((string)($v ?? ''));
                    if ($s === '') return 0.0;
                    $s = str_replace([',', ' ', '$'], '', $s);
                    $s = preg_replace('/[^0-9.\-]/', '', $s);
                    if ($s === '' || $s === '.' || $s === '-') return 0.0;
                    return (float)$s;
                };

                // 1. Collect and sanitize input
                $data = [
                    'reference_no'            => $postData['reference_no'] ?? null,
                    'main_zone_code'          => $postData['main_zone_code'] ?? '',
                    'zone_code'               => $postData['zone_code'] ?? '',
                    'region_code'             => $postData['region_code'] ?? '',
                    'cost_center_code'        => $postData['cost_center_code'] ?? '',
                    'branch_name'             => $postData['branch_name'] ?? '',
                    'asset_name'              => trim((string)($postData['asset_name'] ?? $postData['description'] ?? '')),
                    'months'                  => (int)($postData['months'] ?? 0),
                    'depreciation_code'       => $postData['depreciation_code'] ?? '',
                    'item_gl_code'            => $postData['item_gl_code'] ?? $postData['asset_code'] ?? '',
                    'description'             => trim($postData['description'] ?? ''),
                    'serial_number'           => $postData['serial_number'] ?? null,
                    'quantity'                => (int)($postData['quantity'] ?? 1),
                    'property_type'           => $postData['property_type'] ?? 'PURCHASED',
                    'date_received'           => $postData['date_received'] ?? '',
                    'depreciation_start_date' => $postData['depreciation_start_date'] ?? '',
                    'depreciation_end_date'   => $postData['depreciation_end_date'] ?? '',
                    'depreciation_on'         => $postData['depreciation_on'] ?? 'LAST_DAY',
                    'depreciation_day'        => !empty($postData['depreciation_day']) ? (int)$postData['depreciation_day'] : null,
                    'acquisition_cost'        => $normalizeNumber($postData['acquisition_cost'] ?? 0),
                    'cost_unit'               => $normalizeNumber($postData['cost_unit'] ?? 0),
                    'item_code'               => $postData['item_code'] ?? null,
                    'status'                  => $postData['status'] ?? 'ACTIVE'
                ];

                // 2. Auto-generate the System Asset Code
                $year = date('Y');
                $rand = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $data['system_asset_code'] = "LRI-{$data['main_zone_code']}-{$data['zone_code']}-{$year}{$rand}";

                if ($data['depreciation_code'] === '') {
                    return ['success' => false, 'error' => 'Depreciation code is required.'];
                }
                if ($data['item_gl_code'] === '') {
                    return ['success' => false, 'error' => 'Item GL code is required.'];
                }
                if ($data['asset_name'] === '') {
                    return ['success' => false, 'error' => 'Asset name is required.'];
                }
                if ($data['months'] <= 0) {
                    $policyMonthsFallback = $this->getPolicyMonthsByDepreciationCode((string)$data['depreciation_code']);
                    if ($policyMonthsFallback > 0) {
                        $data['months'] = $policyMonthsFallback;
                    }
                }
                if ($data['months'] <= 0) {
                    return ['success' => false, 'error' => 'Asset months must be greater than zero.'];
                }

                $policyMonths = $this->getPolicyMonthsByDepreciationCode((string)$data['depreciation_code']);
                if ($policyMonths <= 0) {
                    return ['success' => false, 'error' => 'Invalid depreciation code or missing policy months.'];
                }
                if ($data['months'] > $policyMonths) {
                    return ['success' => false, 'error' => 'Asset months cannot exceed policy baseline months.'];
                }

                $data['monthly_depreciation'] = round(((float)$data['acquisition_cost']) / max(1, (int)$data['months']), 2);

                // 3. Save to Database (reuse createAsset)
                $result = $this->createAsset($data, $userId);
                return $result;
            } catch (\Exception $e) {
                return ['success' => false, 'error' => 'Server Error: ' . $e->getMessage()];
            }
        }
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
                    asset_name, months, depreciation_code, item_gl_code,
                    description, serial_number, item_code, quantity, property_type,
                    date_received, depreciation_start_date, depreciation_end_date,
                    depreciation_on, depreciation_day,
                    acquisition_cost, cost_unit, monthly_depreciation, status,
                    created_by
                ) VALUES (
                    :system_asset_code, :reference_no, 
                    :main_zone_code, :zone_code, :region_code, :cost_center_code, :branch_name,
                    :asset_name, :months, :depreciation_code, :item_gl_code,
                    :description, :serial_number, :item_code, :quantity, :property_type,
                    :date_received, :depreciation_start_date, :depreciation_end_date,
                    :depreciation_on, :depreciation_day, 
                    :acquisition_cost, :cost_unit, :monthly_depreciation, :status,
                    :created_by
                )
            ";

            // Recompute monthly_depreciation on the server to ensure correctness
            $acq = (float)($data['acquisition_cost'] ?? 0);
            $assetMonths = (int)($data['months'] ?? 0);
            if ($assetMonths <= 0) {
                throw new \InvalidArgumentException('Asset months must be greater than zero.');
            }

            $monthlyToUse = round($acq / $assetMonths, 2);
            // ensure the data array carries the canonical monthly value
            $data['monthly_depreciation'] = $monthlyToUse;

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':system_asset_code'       => $data['system_asset_code'],
                ':reference_no'            => $data['reference_no'],
                ':main_zone_code'          => $data['main_zone_code'],
                ':zone_code'               => $data['zone_code'],
                ':region_code'             => $data['region_code'],
                ':cost_center_code'        => $data['cost_center_code'],
                ':branch_name'             => $data['branch_name'],
                ':asset_name'              => $data['asset_name'],
                ':months'                  => $assetMonths,
                ':depreciation_code'       => $data['depreciation_code'],
                ':item_gl_code'            => $data['item_gl_code'],
                ':description'             => $data['description'],
                ':serial_number'           => $data['serial_number'],
                ':item_code'               => $data['item_code'],
                ':quantity'                => $data['quantity'],
                ':property_type'           => $data['property_type'],
                ':date_received'           => $data['date_received'],
                ':depreciation_start_date' => $data['depreciation_start_date'],
                ':depreciation_end_date'   => $data['depreciation_end_date'],
                ':depreciation_on'         => $data['depreciation_on'],
                ':depreciation_day'        => $data['depreciation_day'],
                ':acquisition_cost'        => $data['acquisition_cost'],
                ':cost_unit'               => $data['cost_unit'],
                ':monthly_depreciation'    => $data['monthly_depreciation'],
                ':status'                  => $data['status'],
                ':created_by'              => $userId
            ]);

            $assetId = (int)$this->db->lastInsertId();

            $runningDepService = new RunningDepreciationService($this->db);
            $runningDepService->initializeForAsset(
                $assetId,
                (float)$data['acquisition_cost'],
                (int)$assetMonths
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
            'group_code' => 'a.depreciation_code',
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
            $where[] = 'a.depreciation_code = :group_code';
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
                OR a.depreciation_code LIKE :search
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
                a.depreciation_code AS group_code,
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
            $branchWhere[] = 'a.depreciation_code = :group_code';
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
        $lifespanMonths = (int)($data['months'] ?? 0);

        if ($lifespanMonths <= 0) {
            return; // Safety check
        }

        // Category GL context from amortization_depreciation + gl_codes
        $categoryStmt = $this->db->prepare(
            'SELECT ad.gl_code AS category_gl_code, gc.account_type AS category_gl_type
             FROM amortization_depreciation ad
             LEFT JOIN gl_codes gc ON gc.gl_code = ad.gl_code
             WHERE ad.depreciation_code = :depreciation_code
             LIMIT 1'
        );
        $categoryStmt->execute([':depreciation_code' => $data['depreciation_code']]);
        $categoryGl = $categoryStmt->fetch(\PDO::FETCH_ASSOC) ?: null;

        if (!$categoryGl || empty($categoryGl['category_gl_code']) || empty($categoryGl['category_gl_type'])) {
            throw new \RuntimeException('Unable to resolve Category GL mapping for depreciation code.');
        }

        // Item GL context from gl_codes
        $itemStmt = $this->db->prepare('SELECT account_type FROM gl_codes WHERE gl_code = :gl_code LIMIT 1');
        $itemStmt->execute([':gl_code' => $data['item_gl_code']]);
        $itemGlType = (string)($itemStmt->fetchColumn() ?: '');

        if ($itemGlType === '') {
            throw new \RuntimeException('Unable to resolve Item GL mapping for item GL code.');
        }

        $acquisitionCost = (float)$data['acquisition_cost'];
        $monthlyDepreciation = (float)$data['monthly_depreciation'];
        $startDate = new \DateTime($data['depreciation_start_date']);
        $specificDay = (int)($data['depreciation_day'] ?? 1);

        // 2. Prepare the exact statement matching depreciation_ledger table
        $sql = "
            INSERT INTO depreciation_ledger (
                asset_id, system_asset_code, description,
                main_zone_code, zone_code, region_code, cost_center_code, branch_name,
                asset_name, months, depreciation_code, property_type,
                acquisition_cost, monthly_depreciation,
                period_date, period_month, period_year,
                periods_elapsed, periods_remaining, period_depreciation_expense,
                accumulated_depreciation, book_value,
                category_gl_code, category_gl_type, category_amount,
                item_gl_code, item_gl_type, item_amount
            ) VALUES (
                :asset_id, :system_asset_code, :description,
                :main_zone_code, :zone_code, :region_code, :cost_center_code, :branch_name,
                :asset_name, :months, :depreciation_code, :property_type,
                :acquisition_cost, :monthly_depreciation,
                :period_date, :period_month, :period_year,
                :periods_elapsed, :periods_remaining, :period_depreciation_expense,
                :accumulated_depreciation, :book_value,
                :category_gl_code, :category_gl_type, :category_amount,
                :item_gl_code, :item_gl_type, :item_amount
            )
        ";
        $insertStmt = $this->db->prepare($sql);

        $accumulatedDepreciation = 0.00;

        $depreciateOn = $data['depreciation_on'] ?? '';

        for ($i = 0; $i < $lifespanMonths; $i++) {
            if ($depreciateOn === 'LAST_DAY') {
                // Always start from the first day of the start month, then add $i months, then set to last day
                $currentPeriodDate = clone $startDate;
                $currentPeriodDate->modify('first day of this month');
                $currentPeriodDate->modify("+{$i} months");
                $currentPeriodDate->modify('last day of this month');
            } elseif ($depreciateOn === 'FIRST_DAY') {
                $currentPeriodDate = clone $startDate;
                $currentPeriodDate->modify('first day of this month');
                $currentPeriodDate->modify("+{$i} months");
            } elseif ($depreciateOn === 'SPECIFIC_DATE') {
                $currentPeriodDate = clone $startDate;
                $currentPeriodDate->modify('first day of this month');
                $currentPeriodDate->modify("+{$i} months");
                $year = (int)$currentPeriodDate->format('Y');
                $month = (int)$currentPeriodDate->format('n');
                $maxDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                $safeDay = min(max(1, $specificDay), $maxDay);
                $currentPeriodDate->setDate($year, $month, $safeDay);
            } else {
                // Default: just add months
                $currentPeriodDate = clone $startDate;
                $currentPeriodDate->modify("+{$i} months");
            }

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
                ':asset_name'                 => $data['asset_name'],
                ':months'                     => $lifespanMonths,
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
                ':category_gl_code'           => $categoryGl['category_gl_code'],
                ':category_gl_type'           => $categoryGl['category_gl_type'],
                ':category_amount'            => $monthlyDepreciation,
                ':item_gl_code'               => $data['item_gl_code'],
                ':item_gl_type'               => $itemGlType,
                ':item_amount'                => $monthlyDepreciation
            ]);
        }
    }

    private function getPolicyMonthsByDepreciationCode(string $depreciationCode): int {
        if (trim($depreciationCode) === '') {
            return 0;
        }

        $stmt = $this->db->prepare('SELECT months FROM amortization_depreciation WHERE depreciation_code = :depreciation_code LIMIT 1');
        $stmt->execute([':depreciation_code' => $depreciationCode]);

        return (int)($stmt->fetchColumn() ?: 0);
    }

}