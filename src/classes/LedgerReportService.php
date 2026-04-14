<?php
namespace App;

class LedgerReportService {
    private \PDO $db;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }

    public function getAssetLedgerReport(int $assetId, array $filters = []): array {
        $asset = $this->getAssetHeader($assetId);
        if (!$asset) {
            return [
                'asset' => null,
                'ledger_rows' => [],
                'fs_rows' => [],
                'totals' => [
                    'row_count' => 0,
                    'ledger_debit' => 0.0,
                    'ledger_credit' => 0.0,
                    'fs_debit' => 0.0,
                    'fs_credit' => 0.0,
                ],
                'filters' => [
                    'date_from' => '',
                    'date_to' => '',
                    'entry_side' => 'ALL',
                    'period_year' => '',
                    'period_month' => '',
                ],
                'options' => [
                    'years' => [],
                    'months' => [],
                ],
            ];
        }

        $dateFrom = trim((string)($filters['date_from'] ?? ''));
        $dateTo = trim((string)($filters['date_to'] ?? ''));
        $entrySide = strtoupper(trim((string)($filters['entry_side'] ?? 'ALL')));
        $periodYear = (int)($filters['period_year'] ?? 0);
        $periodMonth = (int)($filters['period_month'] ?? 0);

        if (!in_array($entrySide, ['ALL', 'DEBIT', 'CREDIT'], true)) {
            $entrySide = 'ALL';
        }

        $where = ['l.asset_id = :asset_id'];
        $params = [':asset_id' => $assetId];

        if ($dateFrom !== '') {
            $where[] = 'l.period_date >= :date_from';
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo !== '') {
            $where[] = 'l.period_date <= :date_to';
            $params[':date_to'] = $dateTo;
        }

        if ($periodYear > 0) {
            $where[] = 'l.period_year = :period_year';
            $params[':period_year'] = $periodYear;
        }

        if ($periodMonth >= 1 && $periodMonth <= 12) {
            $where[] = 'l.period_month = :period_month';
            $params[':period_month'] = $periodMonth;
        }

        if ($entrySide === 'DEBIT') {
            $where[] = "l.gl_debit_code IS NOT NULL AND l.gl_debit_code <> ''";
        } elseif ($entrySide === 'CREDIT') {
            $where[] = "l.gl_credit_code IS NOT NULL AND l.gl_credit_code <> ''";
        }

        $sql = '
            SELECT
                l.id,
                l.asset_id,
                l.period_date,
                l.period_month,
                l.period_year,
                l.system_asset_code,
                l.description,
                l.group_code,
                l.gl_debit_code,
                l.gl_debit_amount,
                l.gl_credit_code,
                l.gl_credit_amount,
                l.period_depreciation_expense,
                l.accumulated_depreciation,
                l.book_value,
                l.created_at,
                ad.description AS debit_account_name,
                al.asset_name AS credit_account_name
            FROM depreciation_ledger l
            LEFT JOIN amortization_depreciation ad
                ON ad.depreciation_code = l.gl_debit_code
            LEFT JOIN assets_lookup al
                ON al.asset_code = l.gl_credit_code
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY l.period_date ASC, l.id ASC
        ';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        $ledgerRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $fsRows = [];
        $ledgerDebit = 0.0;
        $ledgerCredit = 0.0;
        $fsDebit = 0.0;
        $fsCredit = 0.0;

        foreach ($ledgerRows as &$row) {
            $row['uploaded_by'] = $asset['uploaded_by'] ?: 'Unknown';
            $row['journal_ref'] = 'LEDGER-' . $row['id'];

            $rawDebitAmount = (float)$row['gl_debit_amount'];
            $rawCreditAmount = (float)$row['gl_credit_amount'];

            $showDebit = ($entrySide !== 'CREDIT');
            $showCredit = ($entrySide !== 'DEBIT');

            $debitAmount = $showDebit ? $rawDebitAmount : 0.0;
            $creditAmount = $showCredit ? $rawCreditAmount : 0.0;

            if (!$showDebit) {
                $row['gl_debit_code'] = '';
                $row['debit_account_name'] = '';
            }
            if (!$showCredit) {
                $row['gl_credit_code'] = '';
                $row['credit_account_name'] = '';
            }

            $row['gl_debit_amount'] = $debitAmount;
            $row['gl_credit_amount'] = $creditAmount;

            $ledgerDebit += $debitAmount;
            $ledgerCredit += $creditAmount;

            if ($showDebit) {
                $fsRows[] = [
                    'ledger_id' => (int)$row['id'],
                    'journal_ref' => 'LEDGER-' . $row['id'],
                    'period_date' => $row['period_date'],
                    'entry_side' => 'DEBIT',
                    'account_code' => $row['gl_debit_code'],
                    'account_name' => $row['debit_account_name'] ?: $row['description'],
                    'debit_amount' => $rawDebitAmount,
                    'credit_amount' => 0.0,
                    'line_description' => $row['description'],
                    'uploaded_by' => $row['uploaded_by'],
                ];
                $fsDebit += $rawDebitAmount;
            }

            if ($showCredit) {
                $fsRows[] = [
                    'ledger_id' => (int)$row['id'],
                    'journal_ref' => 'LEDGER-' . $row['id'],
                    'period_date' => $row['period_date'],
                    'entry_side' => 'CREDIT',
                    'account_code' => $row['gl_credit_code'],
                    'account_name' => $row['credit_account_name'] ?: $row['description'],
                    'debit_amount' => 0.0,
                    'credit_amount' => $rawCreditAmount,
                    'line_description' => $row['description'],
                    'uploaded_by' => $row['uploaded_by'],
                ];
                $fsCredit += $rawCreditAmount;
            }
        }
        unset($row);

        $options = $this->getPeriodOptions($assetId, $dateFrom, $dateTo);

        return [
            'asset' => $asset,
            'ledger_rows' => $ledgerRows,
            'fs_rows' => $fsRows,
            'totals' => [
                'row_count' => count($ledgerRows),
                'ledger_debit' => round($ledgerDebit, 2),
                'ledger_credit' => round($ledgerCredit, 2),
                'fs_debit' => round($fsDebit, 2),
                'fs_credit' => round($fsCredit, 2),
            ],
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'entry_side' => $entrySide,
                'period_year' => $periodYear > 0 ? (string)$periodYear : '',
                'period_month' => ($periodMonth >= 1 && $periodMonth <= 12) ? (string)$periodMonth : '',
            ],
            'options' => $options,
        ];
    }

    private function getPeriodOptions(int $assetId, string $dateFrom = '', string $dateTo = ''): array {
        $where = ['asset_id = :asset_id'];
        $params = [':asset_id' => $assetId];

        if ($dateFrom !== '') {
            $where[] = 'period_date >= :date_from';
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo !== '') {
            $where[] = 'period_date <= :date_to';
            $params[':date_to'] = $dateTo;
        }

        $whereSql = implode(' AND ', $where);

        $yearStmt = $this->db->prepare('
            SELECT DISTINCT period_year
            FROM depreciation_ledger
            WHERE ' . $whereSql . '
            ORDER BY period_year DESC
        ');
        foreach ($params as $key => $value) {
            $yearStmt->bindValue($key, $value);
        }
        $yearStmt->execute();
        $years = $yearStmt->fetchAll(\PDO::FETCH_COLUMN);

        $monthStmt = $this->db->prepare('
            SELECT DISTINCT period_month
            FROM depreciation_ledger
            WHERE ' . $whereSql . '
            ORDER BY period_month ASC
        ');
        foreach ($params as $key => $value) {
            $monthStmt->bindValue($key, $value);
        }
        $monthStmt->execute();
        $months = $monthStmt->fetchAll(\PDO::FETCH_COLUMN);

        return [
            'years' => array_map('intval', $years),
            'months' => array_map('intval', $months),
        ];
    }

    private function getAssetHeader(int $assetId): ?array {
        $sql = '
            SELECT
                a.id,
                a.system_asset_code,
                a.serial_number,
                a.description,
                a.group_code,
                a.branch_name,
                a.main_zone_code,
                a.zone_code,
                a.region_code,
                a.depreciation_start_date,
                a.depreciation_end_date,
                a.created_at,
                u.username AS uploaded_by
            FROM assets a
            LEFT JOIN users u ON u.id = a.created_by
            WHERE a.id = :asset_id
            LIMIT 1
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':asset_id', $assetId, \PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
