<?php
namespace App;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportService {
    private \PDO $db;
    private ?\PDO $dbMaster;

    public function __construct(\PDO $db, ?\PDO $dbMaster) {
        $this->db       = $db;
        $this->dbMaster = $dbMaster;
    }

    // ══════════════════════════════════════════════════════════════════
    //  NEW: Parse & validate only — returns preview rows, no DB writes
    // ══════════════════════════════════════════════════════════════════
    public function previewImport(string $filePath): array {
        if (!$this->dbMaster) {
            return ['success' => false, 'error' => 'Master Data database connection is not configured.'];
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet       = $spreadsheet->getSheetByName('Sheet1') ?? $spreadsheet->getActiveSheet();
            $rows        = $sheet->toArray();
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Failed to read Excel file: ' . $e->getMessage()];
        }

        if (count($rows) <= 1) {
            return ['success' => false, 'error' => 'The uploaded file contains no data rows.'];
        }

        array_shift($rows); // strip header

        // Pre-fetch categories: name (lowercase) → [code, asset_life_months]
        $catStmt    = $this->db->query("SELECT category_code, category_name, asset_life_months FROM asset_categories");
        $categories = [];
        while ($row = $catStmt->fetch(\PDO::FETCH_ASSOC)) {
            $categories[strtolower(trim($row['category_name']))] = [
                'code' => $row['category_code'],
                'life' => (int)$row['asset_life_months'],
            ];
        }

        $masterCheck = $this->dbMaster->prepare(
            "SELECT zone, region, branch_name, code AS branch_code
               FROM branch_profile
              WHERE zone = ? AND region = ? AND cost_center = ?
              LIMIT 1"
        );

        // Pre-load ALL existing system_asset_codes from DB for O(1) duplicate lookup
        $existingCodes = [];
        $existStmt = $this->db->query("SELECT system_asset_code FROM assets");
        while ($r = $existStmt->fetch(\PDO::FETCH_ASSOC)) {
            $existingCodes[strtolower($r["system_asset_code"])] = true;
        }

        // Track codes seen within THIS file to catch within-batch duplicates
        $seenInFile = [];

        $preview = [];
        $errors  = [];

        foreach ($rows as $index => $row) {
            $rowNum     = $index + 2;
            $rowErrors  = [];

            // ── Column mapping (NEW 9-column format) ────────────────
            // 0: Zone | 1: Region | 2: Cost Center | 3: Branch (display-only, validated)
            // 4: Reference Number | 5: Asset Category | 6: Date Received
            // 7: Acquisition Cost | 8: Description
            $zone       = trim((string)($row[0] ?? ''));
            $region     = trim((string)($row[1] ?? ''));
            $costCenter = trim((string)($row[2] ?? ''));
            $excelBranch = strtoupper(trim((string)($row[3] ?? '')));  // user-supplied, for display only

            $excelRef    = trim((string)($row[4] ?? ''));
            $dbReferenceNo = $excelRef === '' ? null : $excelRef;

            $catName     = strtolower(trim((string)($row[5] ?? '')));

            // Robust date parsing
            $dateRecVal  = $row[6] ?? null;
            if (is_numeric($dateRecVal)) {
                $dateReceived = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$dateRecVal)
                                    ->format('Y-m-d');
            } elseif (!empty($dateRecVal) && strtotime((string)$dateRecVal) !== false) {
                $dateReceived = date('Y-m-d', strtotime((string)$dateRecVal));
            } else {
                $dateReceived = date('Y-m-d');
            }

            // Depreciation start = last day of received month (system rule)
            $depreciationStartDate = date('Y-m-t', strtotime($dateReceived));

            $acqCost    = (float)($row[7] ?? 0);
            $desc       = trim((string)($row[8] ?? ''));

            // ── Validations ──────────────────────────────────────────
            if (empty($zone) || empty($costCenter)) {
                $rowErrors[] = "Missing required branch fields (Zone, Cost Center).";
            }

            if (!empty($costCenter) && !preg_match('/^\d{4}-\d{3}$/', $costCenter)) {
                $rowErrors[] = "Invalid Cost Center format ({$costCenter}). Expected 0000-000.";
            }

            if ($acqCost <= 0) {
                $rowErrors[] = "Acquisition Cost must be greater than zero.";
            }

            if (empty($desc)) {
                $rowErrors[] = "Description is required.";
            }

            // Category lookup — drives both code AND asset life
            $catEntry = $categories[$catName] ?? null;
            if (!$catEntry) {
                $rowErrors[] = "Asset Category '{$row[5]}' does not exist in the system.";
            }

            // Master data branch validation
            $masterData = null;
            if (empty($rowErrors)) {
                $masterCheck->execute([$zone, $region, $costCenter]);
                $masterData = $masterCheck->fetch(\PDO::FETCH_ASSOC);
                if (!$masterData) {
                    $rowErrors[] = "Branch ({$zone}, {$region}, {$costCenter}) not found in Master Data.";
                }
            }

            if (!empty($rowErrors)) {
                $errors[] = "<strong>Row {$rowNum}:</strong> " . implode(" ", $rowErrors);
                // Still add to preview so user can see which row failed
                $preview[] = [
                    'row_num'                => $rowNum,
                    'has_error'              => true,
                    'zone'                   => $zone,
                    'region'                 => $region,
                    'cost_center'            => $costCenter,
                    'branch_name'            => $excelBranch,
                    'reference_no'           => $dbReferenceNo,
                    'category_name'          => $row[5] ?? '',
                    'category_code'          => $catEntry['code'] ?? '—',
                    'asset_life_months'      => $catEntry['life'] ?? '—',
                    'date_received'          => $dateReceived,
                    'depreciation_start'     => $depreciationStartDate,
                    'acquisition_cost'       => $acqCost,
                    'monthly_depreciation'   => 0,
                    'description'            => $desc,
                    'errors'                 => $rowErrors,
                ];
                continue;
            }

            $catCode   = $catEntry['code'];
            $assetLife = $catEntry['life'];
            $monthlyDep = $assetLife > 0 ? round($acqCost / $assetLife, 2) : 0;

            $suffix          = $excelRef !== '' ? $excelRef : strtoupper(substr(uniqid(), -5));
            $systemAssetCode = sprintf("%s-%s-%s-%s", $catCode, $masterData['zone'], $masterData['branch_code'], $suffix);

            // ── Duplicate Detection ──────────────────────────────────
            $codeKey = strtolower($systemAssetCode);
            $isDuplicate = false;

            if (isset($existingCodes[$codeKey])) {
                // Already exists in the database
                $rowErrors[] = "Duplicate: System code {$systemAssetCode} already exists in the database.";
                $isDuplicate = true;
            } elseif (isset($seenInFile[$codeKey])) {
                // Duplicate within this very file
                $rowErrors[] = "Duplicate: System code {$systemAssetCode} appears more than once in this file (first seen on row {$seenInFile[$codeKey]}).";
                $isDuplicate = true;
            }

            if ($isDuplicate) {
                $errors[] = "<strong>Row {$rowNum}:</strong> " . implode(" ", $rowErrors);
                $preview[] = [
                    'row_num'            => $rowNum,
                    'has_error'          => true,
                    'is_duplicate'       => true,
                    'zone'               => $masterData['zone'],
                    'region'             => $masterData['region'],
                    'cost_center'        => $costCenter,
                    'branch_name'        => $masterData['branch_name'],
                    'reference_no'       => $dbReferenceNo,
                    'category_name'      => $row[5],
                    'category_code'      => $catCode,
                    'asset_life_months'  => $assetLife,
                    'date_received'      => $dateReceived,
                    'depreciation_start' => $depreciationStartDate,
                    'acquisition_cost'   => $acqCost,
                    'monthly_depreciation' => $monthlyDep,
                    'description'        => $desc,
                    'system_asset_code'  => $systemAssetCode,
                    'errors'             => $rowErrors,
                ];
                continue;
            }

            // Register in file-level tracker so subsequent rows in same batch are caught
            $seenInFile[$codeKey] = $rowNum;

            $preview[] = [
                'row_num'                => $rowNum,
                'has_error'              => false,
                'zone'                   => $masterData['zone'],
                'region'                 => $masterData['region'],
                'cost_center'            => $costCenter,
                'branch_name'            => $masterData['branch_name'],
                'reference_no'           => $dbReferenceNo,
                'category_name'          => $row[5],
                'category_code'          => $catCode,
                'asset_life_months'      => $assetLife,
                'date_received'          => $dateReceived,
                'depreciation_start'     => $depreciationStartDate,
                'acquisition_cost'       => $acqCost,
                'monthly_depreciation'   => $monthlyDep,
                'description'            => $desc,
                'system_asset_code'      => $systemAssetCode,
                'errors'                 => [],
            ];
        }

        return [
            'success'  => true,
            'preview'  => $preview,
            'errors'   => $errors,
            'hasErrors' => !empty($errors),
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    //  COMMIT: Accepts already-validated preview payload, writes to DB
    // ══════════════════════════════════════════════════════════════════
    public function commitImport(array $previewRows, int $userId): array {
        $this->db->beginTransaction();

        try {
            $insertAsset = $this->db->prepare("
                INSERT INTO assets (
                    system_asset_code, reference_no, category_code,
                    zone, region, cost_center_code, branch_name,
                    asset_code, description,
                    date_received, depreciation_start_date,
                    acquisition_cost, monthly_depreciation, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insertLedger = $this->db->prepare("
                INSERT INTO running_depreciation (
                    asset_id, period_date,
                    period_depreciation_expense, accumulated_depreciation,
                    book_value, generated_by
                ) VALUES (?, CURDATE(), 0.00, 0.00, ?, ?)
            ");

            // Hard guard: re-check for duplicates at commit time
            // (in case the file was processed twice or DB changed between preview and commit)
            $dupCheck = $this->db->prepare(
                "SELECT COUNT(*) FROM assets WHERE system_asset_code = ?"
            );

            $count   = 0;
            $skipped = 0;
            foreach ($previewRows as $r) {
                $dupCheck->execute([$r['system_asset_code']]);
                if ((int)$dupCheck->fetchColumn() > 0) {
                    $skipped++;
                    continue; // silently skip — already in DB
                }

                $insertAsset->execute([
                    $r['system_asset_code'],
                    $r['reference_no'],
                    $r['category_code'],
                    $r['zone'],
                    $r['region'],
                    $r['cost_center'],
                    $r['branch_name'],
                    $r['category_code'],           // asset_code = category_code shorthand
                    $r['description'],
                    $r['date_received'],
                    $r['depreciation_start'],
                    $r['acquisition_cost'],
                    $r['monthly_depreciation'],
                    $userId,
                ]);

                $assetId = $this->db->lastInsertId();
                $insertLedger->execute([$assetId, $r['acquisition_cost'], $userId]);
                $count++;
            }

            $this->db->commit();
            return ['success' => true, 'count' => $count, 'skipped' => $skipped];

        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Database transaction failed: ' . $e->getMessage()];
        }
    }

    // ══════════════════════════════════════════════════════════════════
    //  LEGACY ENTRY POINT (kept for backwards compat — now two-phase)
    // ══════════════════════════════════════════════════════════════════
    public function processImport(string $filePath, int $userId): array {
        $parsed = $this->previewImport($filePath);

        if (!$parsed['success']) {
            return $parsed;
        }

        if ($parsed['hasErrors']) {
            return [
                'success' => false,
                'error'   => 'Import rejected. Please fix the validation errors below and try again.',
                'errors'  => $parsed['errors'],
            ];
        }

        return $this->commitImport($parsed['preview'], $userId);
    }
}