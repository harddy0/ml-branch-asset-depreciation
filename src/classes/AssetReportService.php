<?php
namespace App;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

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
    public function getFilteredAssets(array $filters): array {
        if (empty($filters['date_from']) || empty($filters['date_to'])) {
            return ['data' => [], 'totals' => ['cost' => 0, 'de' => 0, 'ad' => 0, 'bv' => 0]];
        }

        // STRICT INNER JOIN: ONLY returns assets with a generated record in the dates.
        $sql = "
            SELECT 
                a.system_asset_code, 
                a.branch_name, 
                c.category_name, 
                a.description, 
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

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Active Assets');

        // 1. Set Excel Headers
        $headers = [
            'Codes', 'Branches', 'Asset Category', 'Description', 
            'Cost', 'Depreciation', 'Accu. Dep.', 'Asset Lives', 
            'Book Value', 'Date Gen.'
        ];
        
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col++;
        }

        // 2. Populate Data Rows
        $rowNum = 2;
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
            $sheet->setCellValue('J' . $rowNum, $row['period_date']);

            // Apply number formatting for accounting columns
            $sheet->getStyle('E'.$rowNum.':I'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('H'.$rowNum)->getNumberFormat()->setFormatCode('0'); 
            
            $rowNum++;
        }

        // 3. Add Totals Footer
        $sheet->setCellValue('A' . $rowNum, 'TOTALS');
        $sheet->mergeCells('A' . $rowNum . ':D' . $rowNum);
        $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A' . $rowNum . ':I' . $rowNum)->getFont()->setBold(true);
        
        $sheet->setCellValue('E' . $rowNum, $totals['cost']);
        $sheet->setCellValue('F' . $rowNum, $totals['de']);
        $sheet->setCellValue('G' . $rowNum, $totals['ad']);
        $sheet->setCellValue('I' . $rowNum, $totals['bv']);
        $sheet->getStyle('E'.$rowNum.':I'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');

        // 4. Auto-size columns
        foreach (range('A', 'J') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // 5. STRICT BUFFER CLEANUP 
        error_reporting(0); 
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // 6. Output stream to trigger browser download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Active_Assets_Report_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}