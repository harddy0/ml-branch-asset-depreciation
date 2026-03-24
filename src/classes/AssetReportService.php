<?php
namespace App;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class AssetReportService {
    private \PDO $db;
    private ?\PDO $dbMaster;

    public function __construct(\PDO $db, ?\PDO $dbMaster) {
        $this->db = $db;
        $this->dbMaster = $dbMaster;
    }

    /**
     * Fetch unique Zones from Database 2 (masterdata)
     */
    public function getZones(): array {
        if (!$this->dbMaster) return [];
        $stmt = $this->dbMaster->query("SELECT DISTINCT zone FROM branch_profile WHERE zone IS NOT NULL AND zone != '' ORDER BY zone");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Fetch unique Regions based on Zone from Database 2
     */
    public function getRegions(?string $zone = null): array {
        if (!$this->dbMaster) return [];
        $sql = "SELECT DISTINCT region FROM branch_profile WHERE region IS NOT NULL AND region != ''";
        $params = [];
        if (!empty($zone)) {
            $sql .= " AND zone = ?";
            $params[] = $zone;
        }
        $sql .= " ORDER BY region";
        $stmt = $this->dbMaster->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Fetch unique Branch Names based on Zone and Region from Database 2
     */
    public function getBranches(?string $zone = null, ?string $region = null): array {
        if (!$this->dbMaster) return [];
        $sql = "SELECT DISTINCT branch_name FROM branch_profile WHERE branch_name IS NOT NULL AND branch_name != ''";
        $params = [];
        if (!empty($zone)) {
            $sql .= " AND zone = ?";
            $params[] = $zone;
        }
        if (!empty($region)) {
            $sql .= " AND region = ?";
            $params[] = $region;
        }
        $sql .= " ORDER BY branch_name";
        $stmt = $this->dbMaster->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

   /**
     * Fetch filtered asset data and calculate totals strictly by date range
     */
    /**
     * Fetch filtered asset data and calculate totals strictly by date range
     */
    public function getFilteredAssets(array $filters): array {
        if (empty($filters['date_from']) || empty($filters['date_to'])) {
            return ['data' => [], 'totals' => ['cost' => 0, 'de' => 0, 'ad' => 0, 'bv' => 0]];
        }

        // STRICT INNER JOIN: Added c.category_code to the SELECT statement
        $sql = "
            SELECT 
                a.id as asset_id,
                a.system_asset_code, 
                a.reference_no,
                a.zone,
                a.region,
                a.cost_center_code as cost_center,
                a.branch_name, 
                c.category_code,       -- <=== ADDED THIS BACK IN
                c.category_name, 
                c.asset_life_months,
                a.description, 
                a.date_received,
                a.depreciation_start_date,
                a.retirement_date,
                a.acquisition_cost, 
                
                -- Calculated dynamically: Cost / Asset Lives
                (a.acquisition_cost / c.asset_life_months) as period_depreciation_expense, 
                
                rd.accumulated_depreciation, 
                
                -- Subtract elapsed months from the total category life using ROUND()
                CASE 
                    WHEN a.acquisition_cost > 0 AND c.asset_life_months > 0 
                    THEN c.asset_life_months - ROUND(rd.accumulated_depreciation / (a.acquisition_cost / c.asset_life_months), 0)
                    ELSE 0 
                END as remaining_life,
                
                rd.book_value, 
                rd.period_date
            FROM assets a
            JOIN asset_categories c ON a.category_code = c.category_code
            JOIN running_depreciation rd ON a.id = rd.asset_id
            WHERE a.status = 'ACTIVE'
              AND rd.period_date >= :date_from 
              AND rd.period_date <= :date_to
        ";

        $params = [
            ':date_from' => $filters['date_from'],
            ':date_to'   => $filters['date_to']
        ];

        if (!empty($filters['zone'])) {
            $sql .= " AND a.zone = :zone";
            $params[':zone'] = $filters['zone'];
        }

        if (!empty($filters['region'])) {
            $sql .= " AND a.region = :region";
            $params[':region'] = $filters['region'];
        }

        if (!empty($filters['branch_name'])) {
            $sql .= " AND a.branch_name = :branch_name";
            $params[':branch_name'] = $filters['branch_name'];
        }

        $sql .= " ORDER BY rd.period_date DESC, a.branch_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Calculate footer totals
        $totals = ['cost' => 0, 'de' => 0, 'ad' => 0, 'bv' => 0];
        foreach ($data as $row) {
            $totals['cost'] += (float)$row['acquisition_cost'];
            $totals['de']   += (float)$row['period_depreciation_expense'];
            $totals['ad']   += (float)$row['accumulated_depreciation'];
            $totals['bv']   += (float)$row['book_value'];
        }

        return ['data' => $data, 'totals' => $totals];
    }

    /**
     * Generate Excel File strictly from filtered data
     */
    public function exportToExcel(array $filters): void {
        $report = $this->getFilteredAssets($filters);
        $data = $report['data'];
        $totals = $report['totals'];
        $generatedBy = strtoupper($_SESSION['full_name'] ?? 'User');
        $tzName = $_ENV['APP_TIMEZONE'] ?? 'Asia/Manila';
        try {
            $tz = new \DateTimeZone($tzName);
        } catch (\Exception $e) {
            $tz = new \DateTimeZone('Asia/Manila');
        }
        $generatedAt = (new \DateTime('now', $tz))->format('M j, Y g:i A');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Active Assets');

        // 1. Header block (use export image header)
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

        // 2. Set table headers
        $headerRow = 3;
        $headers = [
            'Codes', 'Branches', 'Asset Category', 'Description', 
            'Cost', 'Depreciation', 'Accu. Dep.', 'Asset Lives', 
            'Book Value', 'Date Gen.'
        ];
        
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $headerRow, $header);
            $sheet->getStyle($col . $headerRow)->getFont()->setBold(true);
            $sheet->getStyle($col . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col++;
        }

        // 3. Populate Data Rows
        $rowNum = $headerRow + 1;
        foreach ($data as $row) {
            $sheet->setCellValue('A' . $rowNum, $row['system_asset_code']);
            $sheet->setCellValue('B' . $rowNum, $row['branch_name']);
            $sheet->setCellValue('C' . $rowNum, $row['category_name']);
            $sheet->setCellValue('D' . $rowNum, $row['description']);
            $sheet->setCellValue('E' . $rowNum, $row['acquisition_cost']);
            $sheet->setCellValue('F' . $rowNum, $row['period_depreciation_expense']);
            $sheet->setCellValue('G' . $rowNum, $row['accumulated_depreciation']);
            $sheet->setCellValue('H' . $rowNum, $row['remaining_life']);
            $sheet->setCellValue('I' . $rowNum, $row['book_value']);
            $sheet->setCellValue('J' . $rowNum,
    !empty($row['period_date'])
        ? date('F j, Y', strtotime($row['period_date']))
        : ''
);

            // Apply number formatting for accounting columns
            $sheet->getStyle('E'.$rowNum.':I'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('H'.$rowNum)->getNumberFormat()->setFormatCode('0'); 
            
            $rowNum++;
        }

        // 4. Add Totals Footer
        $sheet->setCellValue('A' . $rowNum, 'TOTALS');
        $sheet->mergeCells('A' . $rowNum . ':D' . $rowNum);
        $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A' . $rowNum . ':I' . $rowNum)->getFont()->setBold(true);
        
        $sheet->setCellValue('E' . $rowNum, $totals['cost']);
        $sheet->setCellValue('F' . $rowNum, $totals['de']);
        $sheet->setCellValue('G' . $rowNum, $totals['ad']);
        $sheet->setCellValue('I' . $rowNum, $totals['bv']);
        $sheet->getStyle('E'.$rowNum.':I'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');

        // 5. Add generated metadata at bottom-left
        $metaRow1 = $rowNum + 2;
        $metaRow2 = $rowNum + 3;
        $sheet->setCellValue('A' . $metaRow1, 'Generated by: ' . $generatedBy);
        $sheet->setCellValue('A' . $metaRow2, 'Generated on: ' . $generatedAt);
        $sheet->mergeCells('A' . $metaRow1 . ':D' . $metaRow1);
        $sheet->mergeCells('A' . $metaRow2 . ':D' . $metaRow2);
        $sheet->getStyle('A' . $metaRow1 . ':D' . $metaRow2)->getFont()->setSize(10);
        $sheet->getStyle('A' . $metaRow1 . ':D' . $metaRow2)
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // 6. Auto-size columns
        foreach (range('A', 'J') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $sheet->getRowDimension(1)->setRowHeight(40);

        // 7. STRICT BUFFER CLEANUP 
        error_reporting(0); 
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // 8. Output stream to trigger browser download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Active_Assets_Report_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}