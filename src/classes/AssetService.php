<?php
namespace App;

class AssetService
{
    // ==========================================
    // VALIDATION CONSTANTS
    // ==========================================
    
    private const REGION_CODE_MAX_LENGTH = 100;

    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    // ==========================================
    // HELPERS
    // ==========================================

    private function normalizeNumber($value): float
    {
        $s = trim((string)($value ?? ''));
        if ($s === '') return 0.0;
        $s = str_replace([',', ' ', '₱', '$'], '', $s);
        $s = preg_replace('/[^0-9.\-]/', '', $s);
        if ($s === '' || $s === '.' || $s === '-') return 0.0;
        return (float)$s;
    }

    private function getAssetGroupById(int $groupId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT ag.id, ag.group_name, ag.actual_months,
                   ag.asset_gl_code, ag.asset_gl_type,
                   ag.expense_gl_code, ag.expense_gl_type,
                   et.policy_months
            FROM asset_groups ag
            JOIN expense_types et ON et.id = ag.expense_type_id
            WHERE ag.id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $groupId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function generateSystemAssetCode(int $groupId, string $mainZoneCode, string $zoneCode): string
    {
        $year = date('Y');
        $rand = str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return "AG-{$groupId}-{$mainZoneCode}-{$zoneCode}-{$year}{$rand}";
    }

    // ==========================================
    // CREATE
    // ==========================================

    /**
     * Entry point from asset_store.php — sanitizes raw POST then delegates to createAsset().
     */
    public function createAssetFromRequest(array $postData, int $userId): array
    {
        try {
            $assetGroupId = (int)($postData['asset_group_id'] ?? 0);
            if ($assetGroupId <= 0) {
                return ['success' => false, 'error' => 'Asset group is required.'];
            }

            $group = $this->getAssetGroupById($assetGroupId);
            if (!$group) {
                return ['success' => false, 'error' => 'Invalid asset group selected.'];
            }

            $mainZoneCode = trim((string)($postData['main_zone_code'] ?? ''));
            $zoneCode     = trim((string)($postData['zone_code'] ?? ''));

            $months = (int)($postData['months'] ?? 0);
            if ($months <= 0) {
                $months = (int)$group['actual_months'];
            }
            if ($months <= 0) {
                return ['success' => false, 'error' => 'Asset months must be greater than zero.'];
            }
            if ($months > (int)$group['policy_months']) {
                return ['success' => false, 'error' => "Asset months cannot exceed the policy maximum of {$group['policy_months']} months."];
            }

            $acquisitionCost = $this->normalizeNumber($postData['acquisition_cost'] ?? 0);
            if ($acquisitionCost <= 0) {
                return ['success' => false, 'error' => 'Acquisition cost must be greater than zero.'];
            }

            $assetName = trim((string)($postData['asset_name'] ?? $postData['description'] ?? ''));
            if ($assetName === '') {
                return ['success' => false, 'error' => 'Asset name is required.'];
            }

            $data = [
                'system_asset_code'       => $this->generateSystemAssetCode($assetGroupId, $mainZoneCode, $zoneCode),
                'reference_no'            => $postData['reference_no'] ?? null,
                'main_zone_code'          => $mainZoneCode,
                'zone_code'               => $zoneCode,
                'region_code'             => trim((string)($postData['region_code'] ?? '')),
                'cost_center_code'        => trim((string)($postData['cost_center_code'] ?? '')),
                'branch_name'             => trim((string)($postData['branch_name'] ?? '')),
                'asset_name'              => $assetName,
                'asset_group_id'          => $assetGroupId,
                'months'                  => $months,
                'description'             => trim((string)($postData['description'] ?? '')),
                'serial_number'           => $postData['serial_number'] ?? null,
                'item_code'               => $postData['item_code'] ?? null,
                'quantity'                => max(1, (int)($postData['quantity'] ?? 1)),
                'property_type'           => $postData['property_type'] ?? 'PURCHASED',
                'date_received'           => $postData['date_received'] ?? null,
                'depreciation_start_date' => $postData['depreciation_start_date'] ?? null,
                'depreciation_end_date'   => $postData['depreciation_end_date'] ?? null,
                'depreciation_on'         => $postData['depreciation_on'] ?? 'LAST_DAY',
                'depreciation_day'        => !empty($postData['depreciation_day']) ? (int)$postData['depreciation_day'] : null,
                'acquisition_cost'        => $acquisitionCost,
                'cost_unit'               => $this->normalizeNumber($postData['cost_unit'] ?? $acquisitionCost),
                'monthly_depreciation'    => round($acquisitionCost / $months, 2),
                'status'                  => $postData['status'] ?? 'ACTIVE',
                // Pass group data through so createAsset() can use it for the ledger
                '_group'                  => $group,
            ];

            return $this->createAsset($data, $userId);

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Server Error: ' . $e->getMessage()];
        }
    }

    /**
     * Core atomic create: assets row + running_depreciation + full ledger schedule.
     * Can be called directly by ImportService with a pre-built payload.
     */
    public function createAsset(array $data, int $userId): array
    {
        try {
            $this->db->beginTransaction();

            // Resolve group if not already passed in
            $group = $data['_group'] ?? $this->getAssetGroupById((int)($data['asset_group_id'] ?? 0));
            if (!$group) {
                throw new \InvalidArgumentException('Invalid asset group.');
            }

            $assetGroupId = (int)$group['id'];
            $months       = (int)$data['months'];
            if ($months <= 0) {
                throw new \InvalidArgumentException('Asset months must be greater than zero.');
            }

            $acquisitionCost     = round((float)$data['acquisition_cost'], 2);
            $monthlyDepreciation = round($acquisitionCost / $months, 2);

            // Ensure system_asset_code is set
            if (empty($data['system_asset_code'])) {
                $data['system_asset_code'] = $this->generateSystemAssetCode(
                    $assetGroupId,
                    (string)($data['main_zone_code'] ?? ''),
                    (string)($data['zone_code'] ?? '')
                );
            }

            // Defensive validation: enforce region_code length limit
            if (!empty($data['region_code']) && strlen($data['region_code']) > self::REGION_CODE_MAX_LENGTH) {
                $data['region_code'] = substr($data['region_code'], 0, self::REGION_CODE_MAX_LENGTH);
            }

            $stmt = $this->db->prepare('
                INSERT INTO assets (
                    system_asset_code, reference_no,
                    main_zone_code, zone_code, region_code, cost_center_code, branch_name,
                    asset_name, asset_group_id, months,
                    description, serial_number, item_code, quantity, property_type,
                    date_received, depreciation_start_date, depreciation_end_date,
                    depreciation_on, depreciation_day,
                    acquisition_cost, cost_unit, monthly_depreciation,
                    status, created_by
                ) VALUES (
                    :system_asset_code, :reference_no,
                    :main_zone_code, :zone_code, :region_code, :cost_center_code, :branch_name,
                    :asset_name, :asset_group_id, :months,
                    :description, :serial_number, :item_code, :quantity, :property_type,
                    :date_received, :depreciation_start_date, :depreciation_end_date,
                    :depreciation_on, :depreciation_day,
                    :acquisition_cost, :cost_unit, :monthly_depreciation,
                    :status, :created_by
                )
            ');

            // normalize optional dates/day to null when empty to match DB schema
            $dateReceived = !empty($data['date_received']) ? $data['date_received'] : null;
            $deprStart    = !empty($data['depreciation_start_date']) ? $data['depreciation_start_date'] : null;
            $deprEnd      = !empty($data['depreciation_end_date']) ? $data['depreciation_end_date'] : null;
            $deprDay      = isset($data['depreciation_day']) && $data['depreciation_day'] !== '' ? (int)$data['depreciation_day'] : null;

            $stmt->execute([
                ':system_asset_code'       => $data['system_asset_code'],
                ':reference_no'            => $data['reference_no'] ?? null,
                ':main_zone_code'          => $data['main_zone_code'] ?? '',
                ':zone_code'               => $data['zone_code'] ?? '',
                ':region_code'             => $data['region_code'] ?? '',
                ':cost_center_code'        => $data['cost_center_code'] ?? '',
                ':branch_name'             => $data['branch_name'] ?? '',
                ':asset_name'              => $data['asset_name'],
                ':asset_group_id'          => $assetGroupId,
                ':months'                  => $months,
                ':description'             => $data['description'] ?? '',
                ':serial_number'           => $data['serial_number'] ?? null,
                ':item_code'               => $data['item_code'] ?? null,
                ':quantity'                => (int)($data['quantity'] ?? 1),
                ':property_type'           => $data['property_type'] ?? 'PURCHASED',
                ':date_received'           => $dateReceived,
                ':depreciation_start_date' => $deprStart,
                ':depreciation_end_date'   => $deprEnd,
                ':depreciation_on'         => $data['depreciation_on'] ?? 'LAST_DAY',
                ':depreciation_day'        => $deprDay,
                ':acquisition_cost'        => $acquisitionCost,
                ':cost_unit'               => round((float)($data['cost_unit'] ?? $acquisitionCost), 2),
                ':monthly_depreciation'    => $monthlyDepreciation,
                ':status'                  => $data['status'] ?? 'ACTIVE',
                ':created_by'              => $userId,
            ]);

            $assetId = (int)$this->db->lastInsertId();

            // Initialize running depreciation state
            $rdService = new RunningDepreciationService($this->db);
            $rdService->initializeForAsset($assetId, $acquisitionCost, $months);

            // Pre-generate full amortization schedule into depreciation_ledger
            $this->generateLedgerSchedule($assetId, $data, $group, $acquisitionCost, $monthlyDepreciation, $months);

            $this->db->commit();

            return ['success' => true, 'asset_id' => $assetId];

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // LEDGER SCHEDULE GENERATION
    // ==========================================

    /**
     * Pre-generates all depreciation_ledger rows for the full asset lifespan.
     * Called once at asset creation inside the same transaction.
     */
    private function generateLedgerSchedule(
        int   $assetId,
        array $data,
        array $group,
        float $acquisitionCost,
        float $monthlyDepreciation,
        int   $months
    ): void {
        if ($months <= 0) return;

        $startDate   = new \DateTime((string)($data['depreciation_start_date'] ?? 'today'));
        $depreciateOn = strtoupper(trim((string)($data['depreciation_on'] ?? 'LAST_DAY')));
        $specificDay  = (int)($data['depreciation_day'] ?? 1);

        // GL A = asset side (typically CREDIT — accumulated depreciation)
        $glACode = $group['asset_gl_code'];
        $glAType = $group['asset_gl_type'];

        // GL B = expense side (typically DEBIT — depreciation expense)
        $glBCode = $group['expense_gl_code'];
        $glBType = $group['expense_gl_type'];

        $sql = '
            INSERT INTO depreciation_ledger (
                asset_id, system_asset_code,
                main_zone_code, zone_code, region_code, cost_center_code, branch_name,
                asset_name, asset_group_id, group_name, months, property_type,
                acquisition_cost, monthly_depreciation,
                period_date, period_month, period_year,
                periods_elapsed, periods_remaining,
                period_depreciation_expense, accumulated_depreciation, book_value,
                gl_a_code, gl_a_type, gl_a_amount,
                gl_b_code, gl_b_type, gl_b_amount
            ) VALUES (
                :asset_id, :system_asset_code,
                :main_zone_code, :zone_code, :region_code, :cost_center_code, :branch_name,
                :asset_name, :asset_group_id, :group_name, :months, :property_type,
                :acquisition_cost, :monthly_depreciation,
                :period_date, :period_month, :period_year,
                :periods_elapsed, :periods_remaining,
                :period_depreciation_expense, :accumulated_depreciation, :book_value,
                :gl_a_code, :gl_a_type, :gl_a_amount,
                :gl_b_code, :gl_b_type, :gl_b_amount
            )
        ';

        $stmt = $this->db->prepare($sql);
        $accumulated = 0.0;

        for ($i = 0; $i < $months; $i++) {
            $periodDate = $this->resolvePeriodDate($startDate, $i, $depreciateOn, $specificDay);

            $periodsElapsed   = $i + 1;
            $periodsRemaining = $months - $periodsElapsed;

            // compute period expense with a pre-snap strategy to avoid rounding drift
            $accumulatedBefore = $accumulated;
            $periodDepExpense = $monthlyDepreciation;
            $accumulated += $periodDepExpense;
            $bookValue    = $acquisitionCost - $accumulated;

            // Final period: ensure totals exactly equal acquisition cost
            if ($periodsRemaining === 0) {
                $periodDepExpense = round($acquisitionCost - $accumulatedBefore, 2);
                $accumulated = $acquisitionCost;
                $bookValue   = 0.00;
            }

// --- STRICT SIGN CONVENTION LOGIC ---
            // Universal Rule: ALL Debits are Negative, ALL Credits are Positive.
            $glAAmount = (strtoupper($glAType) === 'DEBIT') ? -$periodDepExpense : $periodDepExpense;
            
            $glBAmount = (strtoupper($glBType) === 'DEBIT') ? -$periodDepExpense : $periodDepExpense;

            $stmt->execute([
                ':asset_id'                   => $assetId,
                ':system_asset_code'          => $data['system_asset_code'],
                ':main_zone_code'             => !empty($data['main_zone_code']) ? $data['main_zone_code'] : null,
                ':zone_code'                  => !empty($data['zone_code']) ? $data['zone_code'] : null,
                ':region_code'                => !empty($data['region_code']) ? $data['region_code'] : null,
                ':cost_center_code'           => !empty($data['cost_center_code']) ? $data['cost_center_code'] : null,
                ':branch_name'                => !empty($data['branch_name']) ? $data['branch_name'] : null,
                ':asset_name'                 => $data['asset_name'],
                ':asset_group_id'             => (int)$group['id'],
                ':group_name'                 => $group['group_name'],
                ':months'                     => $months,
                ':property_type'              => $data['property_type'] ?? 'PURCHASED',
                ':acquisition_cost'           => $acquisitionCost,
                ':monthly_depreciation'       => $monthlyDepreciation,
                ':period_date'                => $periodDate->format('Y-m-d'),
                ':period_month'               => (int)$periodDate->format('n'),
                ':period_year'                => (int)$periodDate->format('Y'),
                ':periods_elapsed'            => $periodsElapsed,
                ':periods_remaining'          => $periodsRemaining,
                ':period_depreciation_expense'=> $periodDepExpense,
                ':accumulated_depreciation'   => round($accumulated, 2),
                ':book_value'                 => round($bookValue, 2),
                ':gl_a_code'                  => $glACode,
                ':gl_a_type'                  => $glAType,
                ':gl_a_amount'                => $glAAmount,
                ':gl_b_code'                  => $glBCode,
                ':gl_b_type'                  => $glBType,
                ':gl_b_amount'                => $glBAmount,
            ]);
        }
    }
    

    /**
     * Computes the exact period date for a given iteration offset,
     * respecting FIRST_DAY / LAST_DAY / SPECIFIC_DATE rules.
     */
    private function resolvePeriodDate(\DateTime $startDate, int $offset, string $depreciateOn, int $specificDay): \DateTime
    {
        $date = clone $startDate;
        $date->modify('first day of this month');
        $date->modify("+{$offset} months");

        if ($depreciateOn === 'LAST_DAY') {
            $date->modify('last day of this month');
        } elseif ($depreciateOn === 'SPECIFIC_DATE') {
            $year    = (int)$date->format('Y');
            $month   = (int)$date->format('n');
            $maxDay  = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $safeDay = min(max(1, $specificDay), $maxDay);
            $date->setDate($year, $month, $safeDay);
        }
        // FIRST_DAY: already set to first day of month above

        return $date;
    }

    // ==========================================
    // READ — Single Asset
    // ==========================================

    public function getAssetById(int $id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT a.*,
                   ag.group_name,
                   ag.actual_months,
                   ag.asset_gl_code,
                   ag.asset_gl_type,
                   ag.expense_gl_code,
                   ag.expense_gl_type,
                   et.expense_name,
                   et.category_type,
                   et.policy_months,
                   rd.accumulated_depreciation,
                   rd.book_value,
                   rd.periods_elapsed,
                   rd.periods_remaining,
                   rd.last_depreciation_date,
                   rd.is_fully_depreciated,
                   u.username AS created_by_username
            FROM assets a
            LEFT JOIN asset_groups ag ON ag.id = a.asset_group_id
            LEFT JOIN expense_types et ON et.id = ag.expense_type_id
            LEFT JOIN running_depreciation rd ON rd.asset_id = a.id
            LEFT JOIN users u ON u.id = a.created_by
            WHERE a.id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getAssetByCode(string $code): ?array
    {
        $stmt = $this->db->prepare('
            SELECT a.*,
                   ag.group_name,
                   ag.actual_months,
                   ag.asset_gl_code,
                   ag.asset_gl_type,
                   ag.expense_gl_code,
                   ag.expense_gl_type,
                   et.expense_name,
                   et.category_type,
                   et.policy_months,
                   rd.accumulated_depreciation,
                   rd.book_value,
                   rd.periods_elapsed,
                   rd.periods_remaining,
                   rd.last_depreciation_date,
                   rd.is_fully_depreciated,
                   u.username AS created_by_username
            FROM assets a
            LEFT JOIN asset_groups ag ON ag.id = a.asset_group_id
            LEFT JOIN expense_types et ON et.id = ag.expense_type_id
            LEFT JOIN running_depreciation rd ON rd.asset_id = a.id
            LEFT JOIN users u ON u.id = a.created_by
            WHERE a.system_asset_code = :code
            LIMIT 1
        ');
        $stmt->execute([':code' => $code]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // ==========================================
    // READ — Depreciation List (paginated)
    // ==========================================

    public function getDepreciationList(array $options = []): array
    {
        $page    = max(1, (int)($options['page'] ?? 1));
        $perPage = max(1, min(100, (int)($options['per_page'] ?? 50)));

        $search     = trim((string)($options['search'] ?? ''));
        $groupId    = (int)($options['asset_group_id'] ?? 0);   // renamed from group_code
        $branchName = trim((string)($options['branch_name'] ?? ''));
        $dateFrom   = trim((string)($options['date_from'] ?? ''));
        $dateTo     = trim((string)($options['date_to'] ?? ''));
        $status     = trim((string)($options['status'] ?? ''));
        $sortBy     = (string)($options['sort_by'] ?? 'created_at');
        $sortDir    = strtoupper((string)($options['sort_dir'] ?? 'DESC'));

        $sortMap = [
            'serial_number'        => 'a.serial_number',
            'description'          => 'a.description',
            'item_code'            => 'a.item_code',
            'group_name'           => 'ag.group_name',
            'branch_name'          => 'a.branch_name',
            'acquisition_cost'     => 'a.acquisition_cost',
            'monthly_depreciation' => 'a.monthly_depreciation',
            'status'               => 'a.status',
            'depreciation_end_date'=> 'a.depreciation_end_date',
            'created_at'           => 'a.created_at',
            'uploaded_by'          => 'u.username',
        ];

        $safeSortCol = $sortMap[$sortBy] ?? $sortMap['created_at'];
        $safeSortDir = ($sortDir === 'ASC') ? 'ASC' : 'DESC';

        $where  = [];
        $params = [];

        // Status filter — only apply when explicitly selected
        if ($status !== '') {
            $where[]           = 'a.status = :status';
            $params[':status'] = $status;
        }

        if ($groupId > 0) {
            $where[]                = 'a.asset_group_id = :asset_group_id';
            $params[':asset_group_id'] = $groupId;
        }

        if ($branchName !== '') {
            $where[]               = 'a.branch_name = :branch_name';
            $params[':branch_name'] = $branchName;
        }

        if ($dateFrom !== '') {
            $where[]              = 'DATE(a.created_at) >= :date_from';
            $params[':date_from'] = $dateFrom;
        }

        if ($dateTo !== '') {
            $where[]            = 'DATE(a.created_at) <= :date_to';
            $params[':date_to'] = $dateTo;
        }

        if ($search !== '') {
            $where[] = 'a.description LIKE :search';
            $params[':search'] = '%' . $search . '%';
        }

        $whereSql = '';
        if (!empty($where)) {
            $whereSql = 'WHERE ' . implode(' AND ', $where);
        }

        $baseJoin = '
            FROM assets a
            LEFT JOIN asset_groups ag ON ag.id = a.asset_group_id
            LEFT JOIN users u ON u.id = a.created_by
        ';

        // Total count
        $countStmt = $this->db->prepare("SELECT COUNT(*) {$baseJoin} {$whereSql}");
        foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
        $countStmt->execute();
        $total      = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * $perPage;

        // Data rows
        $dataSql = "
            SELECT
                a.id,
                a.system_asset_code,
                a.serial_number,
                a.description,
                a.item_code,
                a.asset_group_id,
                ag.group_name,
                a.branch_name,
                a.acquisition_cost,
                a.monthly_depreciation,
                a.status,
                a.depreciation_end_date,
                a.created_at,
                u.username AS uploaded_by
            {$baseJoin}
            {$whereSql}
            ORDER BY {$safeSortCol} {$safeSortDir}, a.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $dataStmt = $this->db->prepare($dataSql);
        foreach ($params as $k => $v) $dataStmt->bindValue($k, $v);
        $dataStmt->bindValue(':limit',  $perPage, \PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset,  \PDO::PARAM_INT);
        $dataStmt->execute();
        $rows = $dataStmt->fetchAll(\PDO::FETCH_ASSOC);

        // Distinct branches for filter dropdown (respects current filters except branch)
        $branchWhere  = array_filter($where, fn($c) => strpos($c, 'branch_name') === false);
        $branchParams = array_filter($params, fn($k) => $k !== ':branch_name', ARRAY_FILTER_USE_KEY);

        $branchWhere[] = "a.branch_name IS NOT NULL AND a.branch_name <> ''";
        $branchSql     = "SELECT DISTINCT a.branch_name {$baseJoin} WHERE " . implode(' AND ', $branchWhere) . ' ORDER BY a.branch_name ASC';

        $branchStmt = $this->db->prepare($branchSql);
        foreach ($branchParams as $k => $v) $branchStmt->bindValue($k, $v);
        $branchStmt->execute();
        $branches = $branchStmt->fetchAll(\PDO::FETCH_COLUMN);

        return [
            'data'       => $rows,
            'branches'   => $branches,
            'pagination' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => $totalPages,
                'has_prev'    => $page > 1,
                'has_next'    => $page < $totalPages,
            ],
            'sort'    => [
                'sort_by'  => array_key_exists($sortBy, $sortMap) ? $sortBy : 'created_at',
                'sort_dir' => $safeSortDir,
            ],
            'filters' => [
                'search'         => $search,
                'asset_group_id' => $groupId,
                'branch_name'    => $branchName,
                'date_from'      => $dateFrom,
                'date_to'        => $dateTo,
                'status'         => $status,
            ],
        ];
    }

    // ==========================================
    // UPDATE
    // ==========================================

    /**
     * Only allows editing of non-financial, non-classification fields.
     * Financial data is immutable once the ledger is generated.
     */
    public function updateAsset(int $id, array $data): array
    {
        try {
            $stmt = $this->db->prepare('
                UPDATE assets
                SET reference_no  = :reference_no,
                    description   = :description,
                    serial_number = :serial_number
                WHERE id = :id
            ');
            $stmt->execute([
                ':id'           => $id,
                ':reference_no' => $data['reference_no'] ?? null,
                ':description'  => $data['description'] ?? '',
                ':serial_number'=> $data['serial_number'] ?? null,
            ]);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // STATUS CHANGE (Dispose / Sell / Retire)
    // ==========================================

    public function changeStatus(int $id, string $newStatus, ?string $retirementDate = null): array
    {
        $allowed = ['ACTIVE', 'SOLD', 'DISPOSED', 'INACTIVE'];
        if (!in_array($newStatus, $allowed, true)) {
            return ['success' => false, 'error' => 'Invalid status provided.'];
        }

        $sql    = 'UPDATE assets SET status = :status';
        $params = [':id' => $id, ':status' => $newStatus];

        if (in_array($newStatus, ['SOLD', 'DISPOSED'], true) && $retirementDate) {
            $sql                     .= ', retirement_date = :retirement_date';
            $params[':retirement_date'] = $retirementDate;
        }

        $sql .= ' WHERE id = :id';

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}