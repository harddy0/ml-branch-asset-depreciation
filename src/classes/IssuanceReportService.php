<?php
namespace App;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class IssuanceReportService
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get filtered issuance report with pagination and sorting
     */
    public function getFilteredReport(array $filters = []): array
    {
        $page = max(1, (int)($filters['page'] ?? 1));
        $perPage = max(1, min(200, (int)($filters['per_page'] ?? 50)));
        
        $search = trim((string)($filters['search'] ?? ''));
        $zone = trim((string)($filters['zone'] ?? ''));
        $region = trim((string)($filters['region'] ?? ''));
        $branchName = trim((string)($filters['branch_name'] ?? ''));
        $productCategory = trim((string)($filters['product_category'] ?? ''));
        $dateFrom = $this->normalizeDate($filters['date_from'] ?? '');
        $dateTo = $this->normalizeDate($filters['date_to'] ?? '');
        $transferStatus = trim((string)($filters['transfer_status'] ?? ''));
        
        $sortBy = $this->validateSortColumn($filters['sort_by'] ?? 'date_issued');
        $sortDir = strtoupper(($filters['sort_dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        
        $where = [];
        $params = [];
        
        if ($search !== '') {
            $where[] = '(issuance_number LIKE :search OR item_description LIKE :search OR cost_center_raw LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }
        
        if ($zone !== '') {
            $where[] = 'zone = :zone';
            $params[':zone'] = $zone;
        }
        
        if ($region !== '') {
            $where[] = 'region = :region';
            $params[':region'] = $region;
        }
        
        if ($branchName !== '') {
            $where[] = 'branch_name = :branch_name';
            $params[':branch_name'] = $branchName;
        }
        
        if ($productCategory !== '') {
            $where[] = 'product_category = :product_category';
            $params[':product_category'] = $productCategory;
        }
        
        if ($dateFrom !== '') {
            $where[] = 'DATE(date_issued) >= :date_from';
            $params[':date_from'] = $dateFrom;
        }
        
        if ($dateTo !== '') {
            $where[] = 'DATE(date_issued) <= :date_to';
            $params[':date_to'] = $dateTo;
        }
        
        if ($transferStatus !== '') {
            $where[] = 'transfer_status = :transfer_status';
            $params[':transfer_status'] = $transferStatus;
        }
        
        $whereSql = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        
        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM issuance_staging {$whereSql}";
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();
        
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;
        
        // Get paginated data
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
                transferred_asset_id,
                rejection_reason,
                transferred_at,
                created_at,
                updated_at
            FROM issuance_staging
            {$whereSql}
            ORDER BY {$sortBy} {$sortDir}
            LIMIT :limit OFFSET :offset
        ";
        
        $dataStmt = $this->db->prepare($dataSql);
        foreach ($params as $key => $value) {
            $dataStmt->bindValue($key, $value);
        }
        $dataStmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $dataStmt->execute();
        $data = $dataStmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Get totals for filtered data
        $totals = $this->calculateTotals($whereSql, $params);
        
        // Get filter options for dropdowns
        $options = $this->getFilterOptions();
        
        return [
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
            'totals' => $totals,
            'filters' => [
                'search' => $search,
                'zone' => $zone,
                'region' => $region,
                'branch_name' => $branchName,
                'product_category' => $productCategory,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'transfer_status' => $transferStatus,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
            'options' => $options,
        ];
    }
    
    /**
     * Calculate totals for filtered data
     */
    private function calculateTotals(string $whereSql, array $params): array
    {
        $totalsSql = "
            SELECT 
                COALESCE(SUM(total_amount), 0) as total_amount,
                COALESCE(SUM(quantity), 0) as total_quantity,
                COUNT(*) as record_count
            FROM issuance_staging
            {$whereSql}
        ";
        
        $totalsStmt = $this->db->prepare($totalsSql);
        foreach ($params as $key => $value) {
            $totalsStmt->bindValue($key, $value);
        }
        $totalsStmt->execute();
        $result = $totalsStmt->fetch(\PDO::FETCH_ASSOC);
        
        return [
            'total_amount' => (float)($result['total_amount'] ?? 0),
            'total_quantity' => (int)($result['total_quantity'] ?? 0),
            'record_count' => (int)($result['record_count'] ?? 0),
        ];
    }
    
    /**
     * Get distinct values for filter dropdowns
     */
    public function getFilterOptions(): array
    {
        $options = [];
        
        // Zones
        $zoneStmt = $this->db->query("SELECT DISTINCT zone FROM issuance_staging WHERE zone IS NOT NULL AND zone != '' ORDER BY zone ASC");
        $options['zones'] = $zoneStmt->fetchAll(\PDO::FETCH_COLUMN);
        
        // Regions
        $regionStmt = $this->db->query("SELECT DISTINCT region FROM issuance_staging WHERE region IS NOT NULL AND region != '' ORDER BY region ASC");
        $options['regions'] = $regionStmt->fetchAll(\PDO::FETCH_COLUMN);
        
        // Branches
        $branchStmt = $this->db->query("SELECT DISTINCT branch_name FROM issuance_staging WHERE branch_name IS NOT NULL AND branch_name != '' ORDER BY branch_name ASC");
        $options['branches'] = $branchStmt->fetchAll(\PDO::FETCH_COLUMN);
        
        // Product Categories
        $catStmt = $this->db->query("SELECT DISTINCT product_category FROM issuance_staging WHERE product_category IS NOT NULL AND product_category != '' ORDER BY product_category ASC");
        $options['categories'] = $catStmt->fetchAll(\PDO::FETCH_COLUMN);
        
        return $options;
    }
    
    /**
     * Export filtered data to Excel
     */
    public function exportToExcel(array $filters): void
    {
        $filters['per_page'] = 10000; // Get all data for export
        $data = $this->getFilteredReport($filters);
        $rows = $data['data'];
        $totals = $data['totals'];
        
        $generatedBy = strtoupper($_SESSION['full_name'] ?? 'User');
        $generatedAt = (new \DateTime('now', new \DateTimeZone('Asia/Manila')))->format('M j, Y g:i A');
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Issuance Report');
        
        // Add header image if exists
        $headerImagePath = dirname(__DIR__, 2) . '/public/assets/img/excel_header.png';
        if (file_exists($headerImagePath)) {
            $drawing = new Drawing();
            $drawing->setName('Excel Header');
            $drawing->setPath($headerImagePath);
            $drawing->setHeight(48);
            $drawing->setCoordinates('A1');
            $drawing->setWorksheet($sheet);
        }
        
        // Headers
        $headers = [
            'Date Issued', 'Issuance #', 'Item Code', 'Item Description',
            'Quantity', 'UoM', 'Cost Center', 'Unit Cost', 'Total Amount',
            'Remarks', 'Product Category', 'Zone', 'Region', 'Branch Name', 'Status'
        ];
        
        $headerRow = 3;
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $headerRow, $header);
            $sheet->getStyle($col . $headerRow)->getFont()->setBold(true);
            $sheet->getStyle($col . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }
        
        // Data rows
        $rowNum = $headerRow + 1;
        foreach ($rows as $row) {
            $sheet->setCellValue('A' . $rowNum, $row['date_issued'] ?? '');
            $sheet->setCellValue('B' . $rowNum, $row['issuance_number'] ?? '');
            $sheet->setCellValue('C' . $rowNum, $row['item_code'] ?? '');
            $sheet->setCellValue('D' . $rowNum, $row['item_description'] ?? '');
            $sheet->setCellValue('E' . $rowNum, $row['quantity'] ?? 0);
            $sheet->setCellValue('F' . $rowNum, $row['uom'] ?? '');
            $sheet->setCellValue('G' . $rowNum, $row['cost_center_raw'] ?? '');
            $sheet->setCellValue('H' . $rowNum, $row['unit_cost'] ?? 0);
            $sheet->setCellValue('I' . $rowNum, $row['total_amount'] ?? 0);
            $sheet->setCellValue('J' . $rowNum, $row['description_remarks'] ?? '');
            $sheet->setCellValue('K' . $rowNum, $row['product_category'] ?? '');
            $sheet->setCellValue('L' . $rowNum, $row['zone'] ?? '');
            $sheet->setCellValue('M' . $rowNum, $row['region'] ?? '');
            $sheet->setCellValue('N' . $rowNum, $row['branch_name'] ?? '');
            $sheet->setCellValue('O' . $rowNum, $row['source_status'] ?? 'done');
            
            // Format numbers
            $sheet->getStyle('H' . $rowNum . ':I' . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('E' . $rowNum)->getNumberFormat()->setFormatCode('#,##0');
            
            $rowNum++;
        }
        
        // Totals row
        $sheet->setCellValue('A' . $rowNum, 'TOTALS');
        $sheet->mergeCells('A' . $rowNum . ':G' . $rowNum);
        $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A' . $rowNum . ':O' . $rowNum)->getFont()->setBold(true);
        $sheet->setCellValue('H' . $rowNum, '');
        $sheet->setCellValue('I' . $rowNum, $totals['total_amount']);
        $sheet->getStyle('I' . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
        
        // Metadata
        $metaRow1 = $rowNum + 2;
        $metaRow2 = $rowNum + 3;
        $sheet->setCellValue('A' . $metaRow1, 'Generated by: ' . $generatedBy);
        $sheet->setCellValue('A' . $metaRow2, 'Generated on: ' . $generatedAt);
        $sheet->mergeCells('A' . $metaRow1 . ':E' . $metaRow1);
        $sheet->mergeCells('A' . $metaRow2 . ':E' . $metaRow2);
        $sheet->getStyle('A' . $metaRow1 . ':E' . $metaRow2)->getFont()->setSize(10);
        
        // Output
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Issuance_Report_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    
    private function normalizeDate($value): string
    {
        $value = trim((string)$value);
        if ($value === '') return '';
        $ts = strtotime($value);
        return $ts !== false ? date('Y-m-d', $ts) : '';
    }
    
    private function validateSortColumn(string $column): string
    {
        $allowed = [
            'date_issued', 'issuance_number', 'item_code', 'item_description',
            'quantity', 'cost_center_raw', 'unit_cost', 'total_amount',
            'product_category', 'zone', 'region', 'branch_name', 'created_at'
        ];
        
        return in_array($column, $allowed, true) ? $column : 'date_issued';
    }
}