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

        [$where, $params] = $this->buildLedgerWhere($assetId, $dateFrom, $dateTo, $periodYear, $periodMonth);

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

            // Resolve which GL leg is DEBIT and which is CREDIT
            [$debitCode, $debitDesc, $debitAmt, $creditCode, $creditDesc, $creditAmt]
                = $this->resolveDebitCredit($row);

            // Apply entry_side filter
            $showDebit  = ($entrySide !== 'CREDIT');
            $showCredit = ($entrySide !== 'DEBIT');

            $effectiveDebit  = $showDebit  ? $debitAmt  : 0.0;
            $effectiveCredit = $showCredit ? $creditAmt : 0.0;

            // Enrich row with resolved sides for front-end convenience
            $row['gl_debit_code']        = $showDebit  ? $debitCode  : '';
            $row['gl_debit_description'] = $showDebit  ? $debitDesc  : '';
            $row['gl_debit_amount']      = $effectiveDebit;
            $row['gl_credit_code']       = $showCredit ? $creditCode : '';
            $row['gl_credit_description']= $showCredit ? $creditDesc : '';
            $row['gl_credit_amount']     = $effectiveCredit;

            $ledgerDebit  += $effectiveDebit;
            $ledgerCredit += $effectiveCredit;

            // Build FS (financial statement) split rows
            if ($showDebit) {
                $fsRows[] = [
                    'ledger_id'        => (int)$row['id'],
                    'journal_ref'      => $row['journal_ref'],
                    'period_date'      => $row['period_date'],
                    'entry_side'       => 'DEBIT',
                    'account_code'     => $debitCode,
                    'account_name'     => $debitDesc ?: $row['asset_name'],
                    'debit_amount'     => $debitAmt,
                    'credit_amount'    => 0.0,
                    'line_description' => $row['asset_name'],
                    'uploaded_by'      => $uploadedBy,
                ];
                $fsDebit += $debitAmt;
            }

            if ($showCredit) {
                $fsRows[] = [
                    'ledger_id'        => (int)$row['id'],
                    'journal_ref'      => $row['journal_ref'],
                    'period_date'      => $row['period_date'],
                    'entry_side'       => 'CREDIT',
                    'account_code'     => $creditCode,
                    'account_name'     => $creditDesc ?: $row['asset_name'],
                    'debit_amount'     => 0.0,
                    'credit_amount'    => $creditAmt,
                    'line_description' => $row['asset_name'],
                    'uploaded_by'      => $uploadedBy,
                ];
                $fsCredit += $creditAmt;
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

    private function buildLedgerWhere(
        int    $assetId,
        string $dateFrom,
        string $dateTo,
        int    $periodYear,
        int    $periodMonth
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
        if ($periodYear > 0) {
            $where[]               = 'l.period_year = :period_year';
            $params[':period_year'] = $periodYear;
        }
        if ($periodMonth >= 1 && $periodMonth <= 12) {
            $where[]                = 'l.period_month = :period_month';
            $params[':period_month'] = $periodMonth;
        }

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
}