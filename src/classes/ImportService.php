<?php
namespace App;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportService {
    private \PDO $db;
    private ?\PDO $dbMaster;

    public function __construct(\PDO $db, ?\PDO $dbMaster) {
        $this->db = $db;
        $this->dbMaster = $dbMaster;
    }

    public function processImport(string $filePath, int $userId): array {
        if (!$this->dbMaster) {
            return ['success' => false, 'error' => 'Master Data database connection is not configured.'];
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getSheetByName('Sheet1') ?? $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Failed to read Excel file: ' . $e->getMessage()];
        }

        if (count($rows) <= 1) {
            return ['success' => false, 'error' => 'The uploaded file contains no data rows.'];
        }

        $headers = array_shift($rows); // Remove Headers
        $successCount = 0;
        $errors = [];

        // Pre-fetch asset categories to map Category Name to Category Code
        $catStmt = $this->db->query("SELECT category_code, category_name FROM asset_categories");
        $categories = [];
        while ($row = $catStmt->fetch(\PDO::FETCH_ASSOC)) {
            $categories[strtolower(trim($row['category_name']))] = $row['category_code'];
        }

        // START TRANSACTION: All or Nothing
        $this->db->beginTransaction();

        try {
            $masterCheck = $this->dbMaster->prepare(
                "SELECT zone, code FROM branch_profile WHERE zone = ? AND region = ? AND cost_center = ? LIMIT 1"
            );

            $insertAsset = $this->db->prepare("
                INSERT INTO assets (
                    system_asset_code, reference_no, category_code, zone, region, cost_center_code, branch_name, 
                    asset_code, description, date_received, depreciation_start_date, acquisition_cost, monthly_depreciation, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insertLedger = $this->db->prepare("
                INSERT INTO running_depreciation (
                    asset_id, period_date, period_depreciation_expense, accumulated_depreciation, book_value, generated_by
                ) VALUES (?, CURDATE(), 0.00, 0.00, ?, ?)
            ");

            foreach ($rows as $index => $row) {
                $rowNum = $index + 2; 
                $rowErrors = [];
                
                $zone       = trim((string)($row[0] ?? ''));
                $region     = trim((string)($row[1] ?? ''));
                $costCenter = trim((string)($row[2] ?? ''));
                $branch     = strtoupper(trim((string)($row[3] ?? '')));
                
                $excelRef   = trim((string)($row[4] ?? '')); 
                $dbReferenceNo = $excelRef === '' ? null : $excelRef;

                $catName    = strtolower(trim((string)($row[5] ?? '')));
                $assetCode  = trim((string)($row[6] ?? ''));
                
                // --- THE FIX: ROBUST DATE PARSING ---
                $dateRecVal = $row[7] ?? null;
                if (is_numeric($dateRecVal)) {
                    // It's an Excel Serial Number
                    $dateReceived = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateRecVal)->format('Y-m-d');
                } elseif (!empty($dateRecVal) && strtotime((string)$dateRecVal) !== false) {
                    // It's a standard text date like '2026-03-15'
                    $dateReceived = date('Y-m-d', strtotime((string)$dateRecVal));
                } else {
                    // Failsafe only if completely blank
                    $dateReceived = date('Y-m-d');
                }

                // Depreciation Start Date strictly calculated as the LAST DAY ('t') of the Received month
                $depreciationStartDate = date('Y-m-t', strtotime($dateReceived));
                // ------------------------------------

                $acqCost    = (float)($row[9] ?? 0);
                $assetLife  = (int)($row[10] ?? 12);
                $desc       = trim((string)($row[11] ?? ''));

                if (empty($zone) || empty($costCenter) || empty($branch)) {
                    $rowErrors[] = "Missing required branch fields.";
                }

                if (!empty($costCenter) && !preg_match('/^\d{4}-\d{3}$/', $costCenter)) {
                    $rowErrors[] = "Invalid Cost Center format ({$costCenter}). Expected 0000-000.";
                }

                if (strlen($assetCode) > 50) {
                    $rowErrors[] = "Asset Code is too long (max 50 characters allowed).";
                }

                if ($assetLife < 1 || $assetLife > 120) {
                    $rowErrors[] = "Asset life out of range ({$assetLife} months).";
                }

                $masterZone = '';
                $masterBranchCode = '';
                if (empty($rowErrors)) {
                    $masterCheck->execute([$zone, $region, $costCenter]);
                    $masterData = $masterCheck->fetch(\PDO::FETCH_ASSOC);
                    
                    if (!$masterData) {
                        $rowErrors[] = "Branch Profile ({$zone}, {$region}, {$costCenter}) not found in Master Data.";
                    } else {
                        $masterZone = $masterData['zone'];
                        $masterBranchCode = $masterData['code']; 
                    }
                }

                $catCode = $categories[$catName] ?? null;
                if (!$catCode) {
                    $rowErrors[] = "Asset Category '{$row[5]}' does not exist in the system.";
                }

                if (!empty($rowErrors)) {
                    $errors[] = "<strong>Row {$rowNum}:</strong> " . implode(" ", $rowErrors);
                    continue; 
                }

                if (empty($errors)) {
                    $suffix = $excelRef !== '' ? $excelRef : strtoupper(substr(uniqid(), -5));
                    $systemAssetCode = sprintf("%s-%s-%s-%s", $catCode, $masterZone, $masterBranchCode, $suffix);
                    
                    $monthlyDepreciation = $assetLife > 0 ? ($acqCost / $assetLife) : 0;

                    $insertAsset->execute([
                        $systemAssetCode, $dbReferenceNo, $catCode, $zone, $region, $costCenter, $branch, 
                        $assetCode, $desc, $dateReceived, $depreciationStartDate, $acqCost, $monthlyDepreciation, $userId
                    ]);
                    
                    $assetId = $this->db->lastInsertId();

                    $insertLedger->execute([$assetId, $acqCost, $userId]);
                    $successCount++;
                }
            }

            if (!empty($errors)) {
                $this->db->rollBack(); 
                return [
                    'success' => false, 
                    'error' => 'Import rejected. Please fix the validation errors below and try again.', 
                    'errors' => $errors
                ];
            }

            $this->db->commit(); 
            return ['success' => true, 'count' => $successCount];

        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Database transaction failed: ' . $e->getMessage()];
        }
    }
}