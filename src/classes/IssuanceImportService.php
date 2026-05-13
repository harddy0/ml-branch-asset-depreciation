<?php
namespace App;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class IssuanceImportService
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    private function parseDate($value): ?string
    {
        if (empty($value)) return null;
        if ($value instanceof \DateTimeInterface) return $value->format('Y-m-d');
        if (is_numeric($value)) {
            try { return ExcelDate::excelToDateTimeObject((float)$value)->format('Y-m-d'); } catch (\Throwable $e) { return null; }
        }
        $ts = strtotime(trim((string)$value));
        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    private function normalizeNumber($value): float
    {
        $s = trim((string)($value ?? ''));
        if ($s === '') return 0.0;
        $s = str_replace([',', ' ', '\t', '\n', '\r', '₱', '$'], '', $s);
        $s = preg_replace('/[^0-9.\-]/', '', $s);
        if ($s === '' || $s === '.' || $s === '-') return 0.0;
        return (float)$s;
    }

    private function buildDateTimeForDb(?string $date): ?string
    {
        if (empty($date)) return null;
        if (strlen($date) === 10) return $date . ' 00:00:00';
        return $date;
    }

    /**
     * Generate duplicate key from multiple columns (complete equality only)
     */
    private function getDuplicateKey(array $data): string
    {
        $parts = [
            trim(strtoupper((string)($data['issuance_number'] ?? ''))),
            trim(strtoupper((string)($data['item_description'] ?? ''))),
            round($this->normalizeNumber($data['total_amount'] ?? 0), 2),
            trim(strtoupper((string)($data['cost_center_raw'] ?? ''))),
            trim(strtoupper((string)($data['product_category'] ?? ''))),
            (int)($data['quantity'] ?? 0),
        ];
        
        return md5(implode('|', $parts));
    }

    // ============================================================
    //  PHASE 1: PREVIEW
    // ============================================================
    public function previewImport(string $filePath): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $rows = $spreadsheet->getActiveSheet()->toArray();
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Failed to read Excel file: ' . $e->getMessage()];
        }

        if (count($rows) <= 1) return ['success' => false, 'error' => 'File contains no data.'];

        $expectedHeaders = [
            'date issued', 'issuance number', 'item code', 'item description',
            'quantity', 'uom', 'cost center', 'unit cost', 'total amount',
            'description/remarks', 'product category', 'zone', 'region',
            'branch name', 'status',
        ];

        $normalizeHeader = static function ($value): string {
            $value = strtolower(trim((string)$value));
            $value = preg_replace('/\s+/', ' ', $value);
            return $value;
        };

        $headerRowIndex = null;
        $headerStartCol = null;

        $maxScanRows = min(20, count($rows));
        for ($rIndex = 0; $rIndex < $maxScanRows; $rIndex++) {
            $row = $rows[$rIndex];
            foreach ($row as $cIndex => $cell) {
                if ($normalizeHeader($cell) !== $expectedHeaders[0]) continue;

                $matches = 0;
                for ($i = 0; $i < count($expectedHeaders); $i++) {
                    $val = $row[$cIndex + $i] ?? '';
                    if ($normalizeHeader($val) === $expectedHeaders[$i]) {
                        $matches++;
                    }
                }

                if ($matches >= 10) {
                    $headerRowIndex = $rIndex;
                    $headerStartCol = $cIndex;
                    break 2;
                }
            }
        }

        if ($headerRowIndex === null) {
            $headerRowIndex = 0;
            $headerStartCol = 0;
        }

        // Batch load existing keys from database
        $existingKeys = [];
        try {
            $stmt = $this->db->query('
                SELECT issuance_number, item_description, total_amount, cost_center_raw, product_category, quantity
                FROM issuance_staging
            ');
            while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $key = $this->getDuplicateKey($r);
                $existingKeys[$key] = true;
            }
        } catch (\Throwable $e) {
            $existingKeys = [];
        }

        $preview = [];
        $errors = [];
        $seenKeysInFile = [];

        foreach ($rows as $index => $row) {
            if ($index <= $headerRowIndex) {
                continue;
            }

            $rowNum = $index + 1;
            $rowErrors = [];
            $base = (int)$headerStartCol;

            $dateIssued      = $this->parseDate($row[$base + 0] ?? null);
            $issuanceNumber  = trim((string)($row[$base + 1] ?? ''));
            $itemCode        = trim((string)($row[$base + 2] ?? ''));
            $itemDescription = trim((string)($row[$base + 3] ?? ''));
            $quantity        = (int)($row[$base + 4] ?? 1);
            $uom             = trim((string)($row[$base + 5] ?? ''));
            $costCenter      = trim((string)($row[$base + 6] ?? ''));
            $unitCost        = $this->normalizeNumber($row[$base + 7] ?? 0);
            $totalAmount     = $this->normalizeNumber($row[$base + 8] ?? 0);
            $remarks         = trim((string)($row[$base + 9] ?? ''));
            $productCategory = trim((string)($row[$base + 10] ?? ''));
            $zone            = trim((string)($row[$base + 11] ?? ''));
            $region          = trim((string)($row[$base + 12] ?? ''));
            $branchName      = trim((string)($row[$base + 13] ?? ''));
            $sourceStatus    = trim((string)($row[$base + 14] ?? ''));

            $isRowEmpty = ($dateIssued === null && $issuanceNumber === '' && $itemCode === ''
                && $itemDescription === '' && $quantity <= 0 && $uom === ''
                && $costCenter === '' && $unitCost <= 0 && $totalAmount <= 0
                && $remarks === '' && $productCategory === '' && $zone === ''
                && $region === '' && $branchName === '' && $sourceStatus === '');
            
            if ($isRowEmpty) {
                continue;
            }

            if ($sourceStatus === '') $sourceStatus = 'done';
            if ($quantity <= 0) $quantity = 0;

            // Validation
            if (!$dateIssued) $rowErrors[] = 'Date Issued is required.';
            if ($issuanceNumber === '') $rowErrors[] = 'Issuance Number is required.';
            if ($itemDescription === '') $rowErrors[] = 'Item Description is required.';
            if ($costCenter === '') $rowErrors[] = 'Cost Center is required.';
            if ($productCategory === '') $rowErrors[] = 'Product Category is required.';
            if ($quantity <= 0) $rowErrors[] = 'Quantity must be greater than 0.';
            if ($unitCost < 0) $rowErrors[] = 'Unit Cost must be 0 or greater.';
            if ($totalAmount < 0) $rowErrors[] = 'Total Amount must be 0 or greater.';
            if ($unitCost <= 0 && $totalAmount <= 0) $rowErrors[] = 'Unit Cost or Total Amount is required.';

            // Auto-calculate missing values
            if ($totalAmount <= 0 && $unitCost > 0 && $quantity > 0) {
                $totalAmount = round($unitCost * $quantity, 2);
            }
            if ($unitCost <= 0 && $totalAmount > 0 && $quantity > 0) {
                $unitCost = round($totalAmount / $quantity, 2);
            }

            $rowData = [
                'issuance_number' => $issuanceNumber,
                'item_description' => $itemDescription,
                'total_amount' => $totalAmount,
                'cost_center_raw' => $costCenter,
                'product_category' => $productCategory,
                'quantity' => $quantity,
            ];

            $duplicateKey = $this->getDuplicateKey($rowData);
            
            $isDuplicate = false;
            $duplicateReason = null;
            
            // Check against database
            if (isset($existingKeys[$duplicateKey])) {
                $isDuplicate = true;
                $duplicateReason = "Duplicate: Exact match already exists in system.";
            }
            // Check against same file
            elseif (isset($seenKeysInFile[$duplicateKey])) {
                $isDuplicate = true;
                $duplicateReason = "Duplicate within file: Row {$seenKeysInFile[$duplicateKey]} has identical data.";
            }
            
            $seenKeysInFile[$duplicateKey] = $rowNum;
            
            if ($isDuplicate && $duplicateReason) {
                $rowErrors[] = $duplicateReason;
            }

            $baseRow = [
                'row_num'            => $rowNum,
                'has_error'          => !empty($rowErrors),
                'is_duplicate'       => $isDuplicate,

                'date_issued'        => $dateIssued,
                'issuance_number'    => $issuanceNumber,
                'item_code'          => $itemCode,
                'item_description'   => $itemDescription,
                'quantity'           => $quantity,
                'uom'                => $uom,
                'cost_center_raw'    => $costCenter,
                'unit_cost'          => $unitCost,
                'total_amount'       => $totalAmount,
                'description_remarks'=> $remarks,
                'product_category'   => $productCategory,
                'zone'               => $zone,
                'region'             => $region,
                'branch_name'        => $branchName,
                'source_status'      => $sourceStatus,
                'errors'             => $rowErrors,
            ];

            if ($baseRow['has_error']) {
                $errors[] = '<strong>Row ' . $rowNum . ':</strong> ' . implode(' ', $rowErrors);
            }

            $preview[] = $baseRow;
        }

        return [
            'success'   => true,
            'preview'   => $preview,
            'hasErrors' => !empty($errors),
            'errors'    => $errors,
        ];
    }

    // ============================================================
    //  PHASE 2: COMMIT
    // ============================================================
    public function prepareAndCommit(array $previewRows, array $selectedNums, array $editedMap = []): array
    {
        $inserted = 0;
        $duplicatesSkipped = 0;
        $errors = [];
        
        $batchSize = 1000;
        $batch = [];
        
        // Preload existing keys from database
        $existingKeys = [];
        try {
            $stmt = $this->db->query('
                SELECT issuance_number, item_description, total_amount, cost_center_raw, product_category, quantity
                FROM issuance_staging
            ');
            while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $key = $this->getDuplicateKey($r);
                $existingKeys[$key] = true;
            }
        } catch (\Throwable $e) {
            $existingKeys = [];
        }

        $this->db->beginTransaction();
        
        try {
            $insertSql = '
                INSERT INTO issuance_staging (
                    date_issued, issuance_number, item_code, item_description,
                    quantity, uom, cost_center_raw,
                    unit_cost, total_amount,
                    description_remarks, product_category,
                    zone, region, branch_name, source_status,
                    created_at
                ) VALUES (
                    :date_issued, :issuance_number, :item_code, :item_description,
                    :quantity, :uom, :cost_center_raw,
                    :unit_cost, :total_amount,
                    :description_remarks, :product_category,
                    :zone, :region, :branch_name, :source_status,
                    NOW()
                )
            ';

            $insertStmt = $this->db->prepare($insertSql);

            $processBatch = function(array &$batch) use (&$inserted, &$duplicatesSkipped, &$errors, $insertStmt, &$existingKeys) {
                if (empty($batch)) return;
                
                foreach ($batch as $data) {
                    $duplicateKey = $this->getDuplicateKey($data);
                    
                    if (isset($existingKeys[$duplicateKey])) {
                        $duplicatesSkipped++;
                        continue;
                    }
                    
                    $insertStmt->execute([
                        ':date_issued'         => $data['date_issued'],
                        ':issuance_number'     => $data['issuance_number'],
                        ':item_code'           => $data['item_code'] ?? '',
                        ':item_description'    => $data['item_description'],
                        ':quantity'            => $data['quantity'],
                        ':uom'                 => $data['uom'] ?? '',
                        ':cost_center_raw'     => $data['cost_center_raw'],
                        ':unit_cost'           => $data['unit_cost'],
                        ':total_amount'        => $data['total_amount'],
                        ':description_remarks' => $data['description_remarks'] ?? '',
                        ':product_category'    => $data['product_category'],
                        ':zone'                => $data['zone'] ?? '',
                        ':region'              => $data['region'] ?? '',
                        ':branch_name'         => $data['branch_name'] ?? '',
                        ':source_status'       => $data['source_status'] ?? 'done',
                    ]);
                    
                    $inserted++;
                    $existingKeys[$duplicateKey] = true;
                }
                
                $batch = [];
            };

            foreach ($previewRows as $row) {
                $rn = strval($row['row_num'] ?? '');
                
                if (!empty($selectedNums) && !in_array($rn, $selectedNums, true)) {
                    continue;
                }

                if (isset($editedMap[$rn])) {
                    foreach ($editedMap[$rn] as $key => $val) {
                        $row[$key] = $val;
                    }
                }

                $dateIssued = $this->buildDateTimeForDb($row['date_issued'] ?? null);
                $issuanceNumber = trim((string)($row['issuance_number'] ?? ''));
                $itemDescription = trim((string)($row['item_description'] ?? ''));
                $costCenter = trim((string)($row['cost_center_raw'] ?? ''));
                $productCategory = trim((string)($row['product_category'] ?? ''));
                $quantity = (int)($row['quantity'] ?? 0);

                if (!$dateIssued || $issuanceNumber === '' || $itemDescription === '' || $costCenter === '' || $productCategory === '' || $quantity <= 0) {
                    $errors[] = "Row {$rn}: Missing required fields.";
                    continue;
                }

                $unitCost = $this->normalizeNumber($row['unit_cost'] ?? 0);
                $totalAmount = $this->normalizeNumber($row['total_amount'] ?? 0);
                
                if ($totalAmount <= 0 && $unitCost > 0 && $quantity > 0) {
                    $totalAmount = round($unitCost * $quantity, 2);
                }
                if ($unitCost <= 0 && $totalAmount > 0 && $quantity > 0) {
                    $unitCost = round($totalAmount / $quantity, 2);
                }

                $batch[] = [
                    'row_num'            => $rn,
                    'date_issued'        => $dateIssued,
                    'issuance_number'    => $issuanceNumber,
                    'item_code'          => trim((string)($row['item_code'] ?? '')),
                    'item_description'   => $itemDescription,
                    'quantity'           => $quantity,
                    'uom'                => trim((string)($row['uom'] ?? '')),
                    'cost_center_raw'    => $costCenter,
                    'unit_cost'          => $unitCost,
                    'total_amount'       => $totalAmount,
                    'description_remarks'=> trim((string)($row['description_remarks'] ?? '')),
                    'product_category'   => $productCategory,
                    'zone'               => trim((string)($row['zone'] ?? '')),
                    'region'             => trim((string)($row['region'] ?? '')),
                    'branch_name'        => trim((string)($row['branch_name'] ?? '')),
                    'source_status'      => trim((string)($row['source_status'] ?? 'done')),
                ];

                if (count($batch) >= $batchSize) {
                    $processBatch($batch);
                }
            }

            if (!empty($batch)) {
                $processBatch($batch);
            }
            
            $this->db->commit();
            
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'count'   => 0,
                'skipped' => 0,
                'errors'  => ['Database error: ' . $e->getMessage()],
            ];
        }

        return [
            'success' => true,
            'count'   => $inserted,
            'skipped' => $duplicatesSkipped,
            'errors'  => $errors,
        ];
    }
}