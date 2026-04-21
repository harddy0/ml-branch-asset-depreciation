<?php
namespace App;

class LedgerReportService
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    // ==========================================
    // PUBLIC API
    // ==========================================

    public function getAssetLedgerReport(int $assetId, array $filters = []): array
    {
        $empty = $this->emptyReport();

        $asset = $this->getAssetHeader($assetId);
        if (!$asset) {
            return $empty;
        }

        $dateFrom    = trim((string)($filters['date_from']   ?? ''));
        $dateTo      = trim((string)($filters['date_to']     ?? ''));
        $entrySide   = strtoupper(trim((string)($filters['entry_side']   ?? 'ALL')));
        $periodYear  = (int)($filters['period_year']  ?? 0);
        $periodMonth = (int)($filters['period_month'] ?? 0);

        if (!in_array($entrySide, ['ALL', 'DEBIT', 'CREDIT'], true)) {
            $entrySide = 'ALL';
        }

        [$where, $params] = $this->buildLedgerWhere($assetId, $dateFrom, $dateTo, $periodYear, $periodMonth, $entrySide);

        $sql = '
            SELECT
                l.id,
                l.asset_id,
                l.system_asset_code,
                l.period_date,
                l.period_month,
                l.period_year,
                l.asset_name,
                l.asset_group_id,
                l.group_name,
                l.branch_name,
                l.main_zone_code,
                l.zone_code,
                l.region_code,
                l.cost_center_code,
                l.months,
                l.property_type,
                l.acquisition_cost,
                l.monthly_depreciation,
                l.periods_elapsed,
                l.periods_remaining,
                l.period_depreciation_expense,
                l.accumulated_depreciation,
                l.book_value,
                l.gl_a_code,
                l.gl_a_type,
                l.gl_a_amount,
                l.gl_b_code,
                l.gl_b_type,
                l.gl_b_amount,
                ga.description AS gl_a_description,
                gb.description AS gl_b_description
            FROM depreciation_ledger l
            LEFT JOIN gl_codes ga ON ga.gl_code = l.gl_a_code
            LEFT JOIN gl_codes gb ON gb.gl_code = l.gl_b_code
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY l.period_date ASC, l.id ASC
        ';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        $ledgerRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $fsRows      = [];
        $ledgerDebit = 0.0;
        $ledgerCredit= 0.0;
        $fsDebit     = 0.0;
        $fsCredit    = 0.0;

        $uploadedBy = $asset['uploaded_by'] ?? 'Unknown';

        foreach ($ledgerRows as &$row) {
            $row['uploaded_by'] = $uploadedBy;
            $row['journal_ref'] = 'LEDGER-' . $row['id'];

            // Get both GL entries with their actual types preserved
            $glSplit = $this->splitDebitCreditAmounts($row);

            // Store GL entries with types for frontend
            $row['gl1_code']        = $glSplit['gl1_code'];
            $row['gl1_description'] = $glSplit['gl1_desc'];
            $row['gl1_amount']      = (float)$glSplit['gl1_amt'];
            $row['gl1_type']        = $glSplit['gl1_type'];
            
            $row['gl2_code']        = $glSplit['gl2_code'];
            $row['gl2_description'] = $glSplit['gl2_desc'];
            $row['gl2_amount']      = (float)$glSplit['gl2_amt'];
            $row['gl2_type']        = $glSplit['gl2_type'];

            // Calculate totals based on actual GL types
            if ($glSplit['gl1_type'] === 'DEBIT') {
                $ledgerDebit += (float)$glSplit['gl1_amt'];
            } else {
                $ledgerCredit += (float)$glSplit['gl1_amt'];
            }
            
            if ($glSplit['gl2_type'] === 'DEBIT') {
                $ledgerDebit += (float)$glSplit['gl2_amt'];
            } else {
                $ledgerCredit += (float)$glSplit['gl2_amt'];
            }

            // Build FS (financial statement) split rows - one row per GL with actual type shown
            if ($entrySide === 'ALL' || $entrySide === $glSplit['gl1_type']) {
                $fsRows[] = [
                    'ledger_id'        => (int)$row['id'],
                    'journal_ref'      => $row['journal_ref'],
                    'period_date'      => $row['period_date'],
                    'entry_side'       => $glSplit['gl1_type'],
                    'account_code'     => $glSplit['gl1_code'],
                    'account_name'     => $glSplit['gl1_desc'] ?: $row['asset_name'],
                    'debit_amount'     => $glSplit['gl1_type'] === 'DEBIT' ? (float)$glSplit['gl1_amt'] : 0.0,
                    'credit_amount'    => $glSplit['gl1_type'] === 'CREDIT' ? (float)$glSplit['gl1_amt'] : 0.0,
                    'line_description' => $row['asset_name'],
                    'uploaded_by'      => $uploadedBy,
                    'periods_elapsed'  => $row['periods_elapsed'],
                    'period_depreciation_expense' => $row['period_depreciation_expense'],
                    'accumulated_depreciation' => $row['accumulated_depreciation'],
                    'book_value'       => $row['book_value'],
                ];
            }

            if ($entrySide === 'ALL' || $entrySide === $glSplit['gl2_type']) {
                $fsRows[] = [
                    'ledger_id'        => (int)$row['id'],
                    'journal_ref'      => $row['journal_ref'],
                    'period_date'      => $row['period_date'],
                    'entry_side'       => $glSplit['gl2_type'],
                    'account_code'     => $glSplit['gl2_code'],
                    'account_name'     => $glSplit['gl2_desc'] ?: $row['asset_name'],
                    'debit_amount'     => $glSplit['gl2_type'] === 'DEBIT' ? (float)$glSplit['gl2_amt'] : 0.0,
                    'credit_amount'    => $glSplit['gl2_type'] === 'CREDIT' ? (float)$glSplit['gl2_amt'] : 0.0,
                    'line_description' => $row['asset_name'],
                    'uploaded_by'      => $uploadedBy,
                    'periods_elapsed'  => $row['periods_elapsed'],
                    'period_depreciation_expense' => $row['period_depreciation_expense'],
                    'accumulated_depreciation' => $row['accumulated_depreciation'],
                    'book_value'       => $row['book_value'],
                ];
            }
        }
        unset($row);

        $options = $this->getPeriodOptions($assetId, $dateFrom, $dateTo);

        return [
            'asset'       => $asset,
            'ledger_rows' => $ledgerRows,
            'fs_rows'     => $fsRows,
            'totals'      => [
                'row_count'    => count($ledgerRows),
                'ledger_debit' => round($ledgerDebit,  2),
                'ledger_credit'=> round($ledgerCredit, 2),
                'fs_debit'     => round($fsDebit,  2),
                'fs_credit'    => round($fsCredit, 2),
            ],
            'filters' => [
                'date_from'    => $dateFrom,
                'date_to'      => $dateTo,
                'entry_side'   => $entrySide,
                'period_year'  => $periodYear  > 0 ? (string)$periodYear  : '',
                'period_month' => ($periodMonth >= 1 && $periodMonth <= 12) ? (string)$periodMonth : '',
            ],
            'options' => $options,
        ];
    }

    // ==========================================
    // PRIVATE HELPERS
    // ==========================================

    /**
     * Determines which GL leg is DEBIT and which is CREDIT from the stored
     * gl_a / gl_b entries (direction is stored in gl_a_type / gl_b_type).
     * Returns [debitCode, debitDesc, debitAmt, creditCode, creditDesc, creditAmt].
     */
    private function resolveDebitCredit(array $row): array
    {
        $aIsDebit = strtoupper($row['gl_a_type']) === 'DEBIT';

        if ($aIsDebit) {
            return [
                $row['gl_a_code'], $row['gl_a_description'] ?? '', (float)$row['gl_a_amount'],
                $row['gl_b_code'], $row['gl_b_description'] ?? '', (float)$row['gl_b_amount'],
            ];
        }

        return [
            $row['gl_b_code'], $row['gl_b_description'] ?? '', (float)$row['gl_b_amount'],
            $row['gl_a_code'], $row['gl_a_description'] ?? '', (float)$row['gl_a_amount'],
        ];
    }

    /**
     * Splits GL A and GL B and returns them with their actual TYPES preserved.
     * Returns array with keys: gl1_code, gl1_desc, gl1_amt, gl1_type, gl2_code, gl2_desc, gl2_amt, gl2_type
     * This preserves the actual GL type (DEBIT/CREDIT) even if both are the same type.
     */
    private function splitDebitCreditAmounts(array $row): array
    {
        $aType = strtoupper($row['gl_a_type'] ?? '');
        $bType = strtoupper($row['gl_b_type'] ?? '');
        
        $aCode = $row['gl_a_code'] ?? '';
        $aDesc = $row['gl_a_description'] ?? '';
        $aAmt = (float)($row['gl_a_amount'] ?? 0);
        
        $bCode = $row['gl_b_code'] ?? '';
        $bDesc = $row['gl_b_description'] ?? '';
        $bAmt = (float)($row['gl_b_amount'] ?? 0);
        
        // Return both GLs with their actual types preserved
        // The frontend will determine what to show based on actual types, not position
        return [
            'gl1_code'  => $aCode,
            'gl1_desc'  => $aDesc,
            'gl1_amt'   => $aAmt,
            'gl1_type'  => $aType,
            'gl2_code'  => $bCode,
            'gl2_desc'  => $bDesc,
            'gl2_amt'   => $bAmt,
            'gl2_type'  => $bType,
        ];
    }

    private function buildLedgerWhere(
        int    $assetId,
        string $dateFrom,
        string $dateTo,
        int    $periodYear,
        int    $periodMonth,
        string $entrySide = 'ALL'
    ): array {
        $where  = ['l.asset_id = :asset_id'];
        $params = [':asset_id' => $assetId];

        if ($dateFrom !== '') {
            $where[]              = 'l.period_date >= :date_from';
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo !== '') {
            $where[]            = 'l.period_date <= :date_to';
            $params[':date_to'] = $dateTo;
        }
        // CUMULATIVE "AS OF" FILTER LOGIC
        if ($periodYear > 0 && $periodMonth >= 1 && $periodMonth <= 12) {
            // "As of Month Year" -> Everything up to the last day of the selected month
            $lastDay = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $periodYear, $periodMonth)));
            $where[] = 'l.period_date <= :as_of_date';
            $params[':as_of_date'] = $lastDay;
        } elseif ($periodYear > 0) {
            // "As of Year" (All months) -> Everything up to the end of the selected year
            $where[] = 'l.period_date <= :as_of_date';
            $params[':as_of_date'] = sprintf('%04d-12-31', $periodYear);
        } elseif ($periodMonth >= 1 && $periodMonth <= 12) {
            // "As of Month" (No year given) -> Fallback to the current year
            $currentYear = (int)date('Y');
            $lastDay = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $currentYear, $periodMonth)));
            $where[] = 'l.period_date <= :as_of_date';
            $params[':as_of_date'] = $lastDay;
        }

        // Add entry_side filter to WHERE clause if not 'ALL'
        // This filters by GL type: at least one GL side must match the requested type
        if ($entrySide === 'DEBIT') {
            $where[] = "(l.gl_a_type = 'DEBIT' OR l.gl_b_type = 'DEBIT')";
        } elseif ($entrySide === 'CREDIT') {
            $where[] = "(l.gl_a_type = 'CREDIT' OR l.gl_b_type = 'CREDIT')";
        }
        // If entrySide === 'ALL', no additional filter needed

        return [$where, $params];
    }

    private function getPeriodOptions(int $assetId, string $dateFrom = '', string $dateTo = ''): array
    {
        $where  = ['asset_id = :asset_id'];
        $params = [':asset_id' => $assetId];

        if ($dateFrom !== '') {
            $where[]              = 'period_date >= :date_from';
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo !== '') {
            $where[]            = 'period_date <= :date_to';
            $params[':date_to'] = $dateTo;
        }

        $whereSql = implode(' AND ', $where);

        $yearStmt = $this->db->prepare(
            "SELECT DISTINCT period_year FROM depreciation_ledger WHERE {$whereSql} ORDER BY period_year DESC"
        );
        foreach ($params as $k => $v) $yearStmt->bindValue($k, $v);
        $yearStmt->execute();

        $monthStmt = $this->db->prepare(
            "SELECT DISTINCT period_month FROM depreciation_ledger WHERE {$whereSql} ORDER BY period_month ASC"
        );
        foreach ($params as $k => $v) $monthStmt->bindValue($k, $v);
        $monthStmt->execute();

        return [
            'years'  => array_map('intval', $yearStmt->fetchAll(\PDO::FETCH_COLUMN)),
            'months' => array_map('intval', $monthStmt->fetchAll(\PDO::FETCH_COLUMN)),
        ];
    }

    private function getAssetHeader(int $assetId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT
                a.id,
                a.system_asset_code,
                a.serial_number,
                a.asset_name,
                a.description,
                a.asset_group_id,
                ag.group_name,
                et.expense_name,
                et.category_type,
                a.branch_name,
                a.main_zone_code,
                a.zone_code,
                a.region_code,
                a.cost_center_code,
                a.months,
                a.acquisition_cost,
                a.monthly_depreciation,
                a.depreciation_start_date,
                a.depreciation_end_date,
                a.depreciation_on,
                a.created_at,
                u.username AS uploaded_by
            FROM assets a
            LEFT JOIN asset_groups ag ON ag.id = a.asset_group_id
            LEFT JOIN expense_types et ON et.id = ag.expense_type_id
            LEFT JOIN users u ON u.id = a.created_by
            WHERE a.id = :asset_id
            LIMIT 1
        ');
        $stmt->bindValue(':asset_id', $assetId, \PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function emptyReport(): array
    {
        return [
            'asset'       => null,
            'ledger_rows' => [],
            'fs_rows'     => [],
            'totals'      => [
                'row_count'    => 0,
                'ledger_debit' => 0.0,
                'ledger_credit'=> 0.0,
                'fs_debit'     => 0.0,
                'fs_credit'    => 0.0,
            ],
            'filters' => [
                'date_from'   => '',
                'date_to'     => '',
                'entry_side'  => 'ALL',
                'period_year' => '',
                'period_month'=> '',
            ],
            'options' => ['years' => [], 'months' => []],
        ];
    }

    public function getLedgerEntries(array $filters = []): array
    {
        $month     = $filters['month'] ?? 'ALL';
        $year      = $filters['year'] ?? 'ALL';
        $entryType = strtoupper($filters['entry_type'] ?? 'ALL'); // 'ALL', 'DEBIT', 'CREDIT'
        $assetId   = (int)($filters['asset_id'] ?? 0); // Optional: if viewing a specific asset modal

        $where = [];
        $params = [];

        if ($month !== 'ALL' && $month !== '') {
            $where[] = "period_month = :month";
            $params[':month'] = (int)$month;
        }

        if ($year !== 'ALL' && $year !== '') {
            $where[] = "period_year = :year";
            $params[':year'] = (int)$year;
        }

        if ($assetId > 0) {
            $where[] = "asset_id = :asset_id";
            $params[':asset_id'] = $assetId;
        }

        $baseWhere = empty($where) ? '1=1' : implode(' AND ', $where);

        // Unpivot the single ledger row into two distinct journal entry lines (Debit & Credit)
        $sql = "
            SELECT * FROM (
                SELECT 
                    id, asset_id, system_asset_code, asset_name, branch_name, 
                    period_date, period_month, period_year,
                    gl_a_code AS gl_code, gl_a_type AS entry_type, gl_a_amount AS amount
                FROM depreciation_ledger
                WHERE {$baseWhere}
                
                UNION ALL
                
                SELECT 
                    id, asset_id, system_asset_code, asset_name, branch_name, 
                    period_date, period_month, period_year,
                    gl_b_code AS gl_code, gl_b_type AS entry_type, gl_b_amount AS amount
                FROM depreciation_ledger
                WHERE {$baseWhere}
            ) AS combined_ledger
        ";

        // Apply the Debit/Credit filter on the newly unpivoted data
        if ($entryType === 'DEBIT') {
            $sql .= " WHERE entry_type = 'DEBIT'";
        } elseif ($entryType === 'CREDIT') {
            $sql .= " WHERE entry_type = 'CREDIT'";
        }

        // Standard financial sorting: Chronological, then by asset, then Debits before Credits
        $sql .= " ORDER BY period_date ASC, system_asset_code ASC, entry_type DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

}