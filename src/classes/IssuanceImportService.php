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

    // ============================================================
    //  PHASE 1: PREVIEW (15-Column Issuance Layout)
    // ============================================================
    public function previewImport(string $filePath): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $rows = $spreadsheet->getActiveSheet()->toArray();
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Failed to read Excel file.'];
        }

        if (count($rows) <= 1) return ['success' => false, 'error' => 'File contains no data.'];

        $expectedHeaders = [
            'date issued',
            'issuance number',
            'item code',
            'item description',
            'quantity',
            'uom',
            'cost center',
            'unit cost',
            'total amount',
            'description/remarks',
            'product category',
            'zone',
            'region',
            'branch name',
            'status',
        ];

        $normalizeHeader = static function ($value): string {
            $value = strtolower(trim((string)$value));
            $value = preg_replace('/\s+/', ' ', $value);
            return $value;
        };

        $headerRowIndex = null;
        $headerStartCol = null;

        foreach ($rows as $rIndex => $row) {
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

        $existing = [];
        try {
            foreach ($this->db->query('SELECT issuance_number FROM issuance_staging') as $r) {
                $existing[strtolower((string)$r['issuance_number'])] = true;
            }
        } catch (\Throwable $e) {
            $existing = [];
        }

        $preview = [];
        $errors = [];
        $seenInFile = [];

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

            if (!$dateIssued) $rowErrors[] = 'Date Issued is required.';
            if ($issuanceNumber === '') $rowErrors[] = 'Issuance Number is required.';
            if ($itemDescription === '') $rowErrors[] = 'Item Description is required.';
            if ($costCenter === '') $rowErrors[] = 'Cost Center is required.';
            if ($productCategory === '') $rowErrors[] = 'Product Category is required.';
            if ($quantity <= 0) $rowErrors[] = 'Quantity must be greater than 0.';
            if ($unitCost < 0) $rowErrors[] = 'Unit Cost must be 0 or greater.';
            if ($totalAmount < 0) $rowErrors[] = 'Total Amount must be 0 or greater.';
            if ($unitCost <= 0 && $totalAmount <= 0) $rowErrors[] = 'Unit Cost or Total Amount is required.';

            if ($totalAmount <= 0 && $unitCost > 0 && $quantity > 0) {
                $totalAmount = round($unitCost * $quantity, 2);
            }

            $isDuplicate = false;
            $key = strtolower($issuanceNumber);
            if ($issuanceNumber !== '') {
                if (isset($existing[$key]) || isset($seenInFile[$key])) {
                    $rowErrors[] = "Duplicate Issuance Number: {$issuanceNumber}";
                    $isDuplicate = true;
                }
                $seenInFile[$key] = $rowNum;
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
    //  PHASE 2: PREPARE AND COMMIT
    // ============================================================
    public function prepareAndCommit(array $previewRows, array $selectedNums, array $editedMap = []): array
    {
        $count = 0;
        $skipped = 0;
        $errors = [];

        $stmt = $this->db->prepare('
            INSERT INTO issuance_staging (
                date_issued, issuance_number, item_code, item_description,
                quantity, uom, cost_center_raw,
                unit_cost, total_amount,
                description_remarks, product_category,
                zone, region, branch_name, source_status
            ) VALUES (
                :date_issued, :issuance_number, :item_code, :item_description,
                :quantity, :uom, :cost_center_raw,
                :unit_cost, :total_amount,
                :description_remarks, :product_category,
                :zone, :region, :branch_name, :source_status
            )
        ');

        foreach ($previewRows as $row) {
            $rn = strval($row['row_num'] ?? '');
            if (!empty($selectedNums) && !in_array($rn, $selectedNums, true)) continue;

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

            try {
                $stmt->execute([
                    ':date_issued'         => $dateIssued,
                    ':issuance_number'     => $issuanceNumber,
                    ':item_code'           => trim((string)($row['item_code'] ?? '')),
                    ':item_description'    => $itemDescription,
                    ':quantity'            => $quantity,
                    ':uom'                 => trim((string)($row['uom'] ?? '')),
                    ':cost_center_raw'     => $costCenter,
                    ':unit_cost'           => $unitCost,
                    ':total_amount'        => $totalAmount,
                    ':description_remarks' => trim((string)($row['description_remarks'] ?? '')),
                    ':product_category'    => $productCategory,
                    ':zone'                => trim((string)($row['zone'] ?? '')),
                    ':region'              => trim((string)($row['region'] ?? '')),
                    ':branch_name'         => trim((string)($row['branch_name'] ?? '')),
                    ':source_status'       => trim((string)($row['source_status'] ?? 'done')),
                ]);
                $count++;
            } catch (\PDOException $e) {
                if ((int)$e->getCode() === 23000) {
                    $skipped++;
                } else {
                    $errors[] = "Row {$rn}: " . $e->getMessage();
                }
            }
        }

        return [
            'success' => ($count > 0 || empty($errors)),
            'count'   => $count,
            'skipped' => $skipped,
            'errors'  => $errors,
        ];
    }
}
