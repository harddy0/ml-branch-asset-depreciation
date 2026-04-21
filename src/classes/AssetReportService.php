<?php
namespace App;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class AssetReportService
{
    private \PDO  $db;
    private ?\PDO $dbMaster;

    public function __construct(\PDO $db, ?\PDO $dbMaster = null)
    {
        $this->db       = $db;
        $this->dbMaster = $dbMaster;
    }

    // ==========================================
    // FILTER DROPDOWN DATA
    // ==========================================

    public function getZones(): array
    {
        $stmt = $this->db->query("
            SELECT DISTINCT main_zone_code
            FROM assets
            WHERE status = 'ACTIVE'
              AND main_zone_code IS NOT NULL
              AND main_zone_code <> ''
            ORDER BY main_zone_code ASC
        ");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getRegions(?string $zone = null): array
    {
        $sql    = "SELECT DISTINCT region_code FROM assets WHERE status = 'ACTIVE' AND region_code IS NOT NULL AND region_code <> ''";
        $params = [];

        if (!empty($zone)) {
            $sql           .= ' AND main_zone_code = ?';
            $params[]       = $zone;
        }

        $sql .= ' ORDER BY region_code ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getBranches(?string $zone = null, ?string $region = null): array
    {
        $sql    = "SELECT DISTINCT branch_name FROM assets WHERE status = 'ACTIVE' AND branch_name IS NOT NULL AND branch_name <> ''";
        $params = [];

        if (!empty($zone)) {
            $sql      .= ' AND main_zone_code = ?';
            $params[]  = $zone;
        }
        if (!empty($region)) {
            $sql      .= ' AND region_code = ?';
            $params[]  = $region;
        }

        $sql .= ' ORDER BY branch_name ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    // ==========================================
    // REPORT DATA
    // ==========================================

    /**
     * Returns depreciation report data filtered by as-of date, zone, region, branch.
     * Joins depreciation_ledger for period values and uses the latest entry per asset.
     */
    public function getFilteredAssets(array $filters): array
    {
        $asOfDate = trim((string)($filters['as_of_date'] ?? ''));
        if ($asOfDate === '') {
            $fallbackTo = trim((string)($filters['date_to'] ?? ''));
            $fallbackFrom = trim((string)($filters['date_from'] ?? ''));
            $asOfDate = $fallbackTo !== '' ? $fallbackTo : $fallbackFrom;
        }

        if ($asOfDate === '') {
            return ['data' => [], 'totals' => ['cost' => 0, 'de' => 0, 'ad' => 0, 'bv' => 0]];
        }

        $sql = '
            SELECT
                a.id                                                          AS asset_id,
                dl.system_asset_code,
                a.reference_no,
                COALESCE(dl.main_zone_code, a.main_zone_code)                 AS zone,
                COALESCE(dl.region_code, a.region_code)                       AS region,
                COALESCE(dl.cost_center_code, a.cost_center_code)             AS cost_center,
                COALESCE(dl.branch_name, a.branch_name)                       AS branch_name,
                a.asset_group_id,
                COALESCE(dl.group_name, ag.group_name)                        AS group_name,
                et.expense_name                                               AS category_name,
                et.category_type,
                a.months                                                      AS asset_life_months,
                a.description,
                a.asset_name,
                a.date_received,
                a.depreciation_start_date,
                a.retirement_date,
                a.acquisition_cost,
                dl.period_depreciation_expense,
                dl.accumulated_depreciation,
                dl.periods_remaining                                          AS remaining_life,
                dl.book_value,
                dl.period_date,
                a.monthly_depreciation
            FROM assets a
            JOIN (
                SELECT d1.*
                FROM depreciation_ledger d1
                JOIN (
                    SELECT asset_id, MAX(period_date) AS period_date
                    FROM depreciation_ledger
                    WHERE period_date <= :as_of_date
                    GROUP BY asset_id
                ) d2 ON d1.asset_id = d2.asset_id AND d1.period_date = d2.period_date
            ) dl ON dl.asset_id = a.id
            LEFT JOIN asset_groups ag ON ag.id = a.asset_group_id
            LEFT JOIN expense_types et ON et.id = ag.expense_type_id
            WHERE a.status = \'ACTIVE\'
        ';

        $params = [
            ':as_of_date' => $asOfDate,
        ];

        if (!empty($filters['zone'])) {
            $sql            .= ' AND COALESCE(dl.main_zone_code, a.main_zone_code) = :zone';
            $params[':zone'] = $filters['zone'];
        }
        if (!empty($filters['region'])) {
            $sql              .= ' AND COALESCE(dl.region_code, a.region_code) = :region';
            $params[':region'] = $filters['region'];
        }
        if (!empty($filters['branch_name'])) {
            $sql                    .= ' AND COALESCE(dl.branch_name, a.branch_name) = :branch_name';
            $params[':branch_name'] = $filters['branch_name'];
        }

        $sql .= ' ORDER BY dl.period_date DESC, COALESCE(dl.branch_name, a.branch_name) ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $totals = ['cost' => 0.0, 'de' => 0.0, 'ad' => 0.0, 'bv' => 0.0];
        foreach ($data as $row) {
            $totals['cost'] += (float)$row['acquisition_cost'];
            $totals['de']   += (float)$row['period_depreciation_expense'];
            $totals['ad']   += (float)$row['accumulated_depreciation'];
            $totals['bv']   += (float)$row['book_value'];
        }

        return ['data' => $data, 'totals' => $totals];
    }

    // ==========================================
    // EXCEL EXPORT
    // ==========================================

    public function exportToExcel(array $filters): void
    {
        $report      = $this->getFilteredAssets($filters);
        $data        = $report['data'];
        $totals      = $report['totals'];
        $generatedBy = strtoupper($_SESSION['full_name'] ?? 'User');
        $tzName      = $_ENV['APP_TIMEZONE'] ?? 'Asia/Manila';

        try {
            $tz = new \DateTimeZone($tzName);
        } catch (\Exception $e) {
            $tz = new \DateTimeZone('Asia/Manila');
        }

        $generatedAt = (new \DateTime('now', $tz))->format('M j, Y g:i A');
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Running Depreciation');

        // Header image
        $headerImagePath = dirname(__DIR__, 2) . '/public/assets/img/excel_header.png';
        if (file_exists($headerImagePath)) {
            $drawing = new Drawing();
            $drawing->setName('Excel Header');
            $drawing->setDescription('Excel Header');
            $drawing->setPath($headerImagePath);
            $drawing->setHeight(48);
            $drawing->setCoordinates('A1');
            $drawing->setWorksheet($sheet);
        }

        $headerRow = 3;
        $headers   = [
            'Asset Code', 'Branch', 'Group', 'Category', 'Description',
            'Cost', 'Depreciation', 'Accu. Dep.', 'Asset Lives',
            'Book Value', 'Date Gen.',
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $headerRow, $header);
            $sheet->getStyle($col . $headerRow)->getFont()->setBold(true);
            $sheet->getStyle($col . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col++;
        }

        $rowNum = $headerRow + 1;
        foreach ($data as $row) {
            $sheet->setCellValue('A' . $rowNum, $row['system_asset_code']);
            $sheet->setCellValue('B' . $rowNum, $row['branch_name']);
            $sheet->setCellValue('C' . $rowNum, $row['group_name']);
            $sheet->setCellValue('D' . $rowNum, $row['category_name']);
            $sheet->setCellValue('E' . $rowNum, $row['description']);
            $sheet->setCellValue('F' . $rowNum, $row['acquisition_cost']);
            $sheet->setCellValue('G' . $rowNum, $row['period_depreciation_expense']);
            $sheet->setCellValue('H' . $rowNum, $row['accumulated_depreciation']);
            $sheet->setCellValue('I' . $rowNum, $row['remaining_life']);
            $sheet->setCellValue('J' . $rowNum, $row['book_value']);
            $sheet->setCellValue('K' . $rowNum,
                !empty($row['period_date']) ? date('F j, Y', strtotime($row['period_date'])) : ''
            );

            $sheet->getStyle('F' . $rowNum . ':J' . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('I' . $rowNum)->getNumberFormat()->setFormatCode('0');

            $rowNum++;
        }

        // Totals row
        $sheet->setCellValue('A' . $rowNum, 'TOTALS');
        $sheet->mergeCells('A' . $rowNum . ':E' . $rowNum);
        $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A' . $rowNum . ':J' . $rowNum)->getFont()->setBold(true);
        $sheet->setCellValue('F' . $rowNum, $totals['cost']);
        $sheet->setCellValue('G' . $rowNum, $totals['de']);
        $sheet->setCellValue('H' . $rowNum, $totals['ad']);
        $sheet->setCellValue('J' . $rowNum, $totals['bv']);
        $sheet->getStyle('F' . $rowNum . ':J' . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');

        // Metadata
        $metaRow1 = $rowNum + 2;
        $metaRow2 = $rowNum + 3;
        $sheet->setCellValue('A' . $metaRow1, 'Generated by: ' . $generatedBy);
        $sheet->setCellValue('A' . $metaRow2, 'Generated on: ' . $generatedAt);
        $sheet->mergeCells('A' . $metaRow1 . ':E' . $metaRow1);
        $sheet->mergeCells('A' . $metaRow2 . ':E' . $metaRow2);
        $sheet->getStyle('A' . $metaRow1 . ':E' . $metaRow2)->getFont()->setSize(10);
        $sheet->getStyle('A' . $metaRow1 . ':E' . $metaRow2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        foreach (range('A', 'K') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet->getRowDimension(1)->setRowHeight(40);

        error_reporting(0);
        while (ob_get_level() > 0) ob_end_clean();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Running_Depreciation_Report_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}