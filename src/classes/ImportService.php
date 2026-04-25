<?php
namespace App;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportService {
    private \PDO  $db;
    private ?\PDO $dbMaster;
    
    // We cache the groups so we can search by text name
    private array $assetGroupsCache = [];

    public function __construct(\PDO $db, ?\PDO $dbMaster) {
        $this->db       = $db;
        $this->dbMaster = $dbMaster;
        $this->loadAssetGroupsCache();
    }

    /**
     * Loads the asset_groups table into memory.
     * This allows us to map the user's text from Excel to the database's integer ID.
     */
    private function loadAssetGroupsCache(): void {
        $stmt = $this->db->query("SELECT id, group_name, actual_months FROM asset_groups");
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $this->assetGroupsCache[] = $row;
        }
    }

    /**
     * Takes the text typed in the Excel file and finds the closest matching database ID.
     * This way, the user never needs to know the Primary Key.
     */
    private function fuzzyMatchAssetGroup(string $rawName): ?array {
        // Strip everything but letters/numbers to make matching highly forgiving
        $raw = strtolower(preg_replace('/[^a-z0-9]/i', '', $rawName));
        if ($raw === '') return null;

        $bestMatch = null;
        $highestPct = 0;

        foreach ($this->assetGroupsCache as $group) {
            $gName = strtolower(preg_replace('/[^a-z0-9]/i', '', $group['group_name']));
            
            // If it's an exact match after stripping spaces, return immediately
            if ($raw === $gName) return $group; 

            // Otherwise, calculate how similar the strings are (e.g. "Furnitue" vs "Furniture")
            similar_text($raw, $gName, $pct);
            if ($pct > $highestPct) {
                $highestPct = $pct;
                $bestMatch = $group;
            }
        }

        // Only accept if it's at least a 75% text match
        return ($highestPct >= 75) ? $bestMatch : null;
    }

    private function parseDate($value): ?string {
        if (empty($value)) return null;
        if ($value instanceof \DateTimeInterface) return $value->format('Y-m-d');
        if (is_numeric($value)) {
            try { return ExcelDate::excelToDateTimeObject((float)$value)->format('Y-m-d'); } catch (\Throwable $e) { return null; }
        }
        $ts = strtotime(trim((string)$value));
        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    private function normalizeNumber($value): float {
        $s = preg_replace('/[^0-9.\-]/', '', (string)$value);
        return is_numeric($s) ? (float)$s : 0.0;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  PHASE 1: PREVIEW (15-Column Layout)
    // ══════════════════════════════════════════════════════════════════════
    public function previewImport(string $filePath): array {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $rows = $spreadsheet->getActiveSheet()->toArray();
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Failed to read Excel file.'];
        }

        if (count($rows) <= 1) return ['success' => false, 'error' => 'File contains no data.'];
        array_shift($rows); // Strip header

        $locSvc = $this->dbMaster ? new LocationMasterService($this->dbMaster) : null;
        
        $existingCodes = [];
        foreach ($this->db->query("SELECT system_asset_code FROM assets") as $r) {
            $existingCodes[strtolower($r['system_asset_code'])] = true;
        }

        $preview = [];
        $errors = [];
        $seenInFile = [];

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;
            $rowErrors = [];

            // 1. Map the new 15 columns
            $serialNumber  = trim((string)($row[0] ?? ''));
            $description   = trim((string)($row[1] ?? ''));
            $referenceNo   = trim((string)($row[2] ?? ''));
            $quantity      = max(1, (int)($row[3] ?? 1));
            $propertyType  = strtoupper(trim((string)($row[4] ?? 'PURCHASED')));
            
            // This is the raw text the user typed in Excel (e.g. "IT Equipment")
            $groupRaw      = trim((string)($row[5] ?? '')); 
            
            $acqCost       = $this->normalizeNumber($row[6] ?? 0);
            $dateReceived  = $this->parseDate($row[7] ?? date('Y-m-d'));
            $mainZone      = trim((string)($row[8] ?? ''));
            $subZone       = trim((string)($row[9] ?? ''));
            $regionStr     = trim((string)($row[10] ?? ''));
            $costCenter    = trim((string)($row[11] ?? ''));
            $branchStr     = trim((string)($row[12] ?? ''));
            $itemCode      = trim((string)($row[13] ?? ''));
            
            // Depreciation Start defaults to Last Day of Received Month
            $deprStart = $this->parseDate($row[14] ?? null) ?: date('Y-m-t', strtotime($dateReceived));

            if (!in_array($propertyType, ['PURCHASED', 'LEASE', 'LEASEHOLD', 'MAINTENANCE'])) $propertyType = 'PURCHASED';

            if (empty($description)) $rowErrors[] = "Description is required.";
            if ($acqCost <= 0) $rowErrors[] = "Acquisition Cost must be > 0.";

            // 2. Translate Text to ID (The Magic Step)
            $groupData = $this->fuzzyMatchAssetGroup($groupRaw);
            
            if (!$groupData) {
                // If the user typed complete nonsense, flag an error
                $rowErrors[] = "Asset Group '{$groupRaw}' does not match any known group in the system.";
                $monthlyDep = 0;
            } else {
                $monthlyDep = $groupData['actual_months'] > 0 ? round($acqCost / $groupData['actual_months'], 2) : 0;
            }

            // 3. Resolve Location (Using the helper added to LocationMasterService)
            $locData = null;
            if ($locSvc) {
                $locData = $locSvc->resolveImportLocation($costCenter, $branchStr, $regionStr);
                if (!empty($locData['errors'])) {
                    $rowErrors = array_merge($rowErrors, $locData['errors']);
                }
            }

            // 4. Generate the new System Asset Code (AST - CC - REF)
            $ccPart  = $locData['cost_center_code'] ?? ($costCenter ?: 'UNKN');
            $refPart = $referenceNo ?: strtoupper(substr(uniqid(), -5));
            $systemAssetCode = "AST-{$ccPart}-{$refPart}";

            // 5. Check for Duplicates
            $isDuplicate = false;
            $codeKey = strtolower($systemAssetCode);
            if (isset($existingCodes[$codeKey]) || isset($seenInFile[$codeKey])) {
                $rowErrors[] = "Duplicate System Code: {$systemAssetCode}";
                $isDuplicate = true;
            }
            $seenInFile[$codeKey] = $rowNum;

            // 6. Build the row payload
            $baseRow = [
                'row_num'                 => $rowNum,
                'has_error'               => !empty($rowErrors),
                'is_duplicate'            => $isDuplicate,
                'system_asset_code'       => $systemAssetCode,
                
                // --- THE INTEGER ID IS SAVED HERE FOR THE DATABASE ---
                'asset_group_id'          => $groupData['id'] ?? null, 
                'group_name'              => $groupData['group_name'] ?? $groupRaw,
                'actual_months'           => $groupData['actual_months'] ?? 0,
                
                'main_zone_code'          => $locData['main_zone_code'] ?? $mainZone,
                'zone_code'               => $locData['zone_code'] ?? $subZone,
                'region_code'             => $locData['region_code'] ?? $regionStr,
                'cost_center_code'        => $locData['cost_center_code'] ?? $costCenter,
                'branch_code'             => $locData['branch_code'] ?? $costCenter,
                'branch_name'             => $locData['branch_name'] ?? $branchStr,
                
                'description'             => $description,
                'serial_number'           => $serialNumber,
                'reference_no'            => $referenceNo,
                'item_code'               => $itemCode,
                'quantity'                => $quantity,
                'property_type'           => $propertyType,
                'date_received'           => $dateReceived,
                'depreciation_start_date' => $deprStart,
                'acquisition_cost'        => $acqCost,
                'monthly_depreciation'    => $monthlyDep,
                'status'                  => 'ACTIVE',
                'errors'                  => $rowErrors
            ];

            if ($baseRow['has_error']) {
                $errors[] = "<strong>Row {$rowNum}:</strong> " . implode(' ', $rowErrors);
            }

            $preview[] = $baseRow;
        }

        return [
            'success'   => true,
            'preview'   => $preview,
            'hasErrors' => !empty($errors),
            'errors'    => $errors,
            'groups'    => $this->assetGroupsCache // Sends valid groups to frontend for the edit modal
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    //  PHASE 2: PREPARE AND COMMIT
    // ══════════════════════════════════════════════════════════════════════
    public function prepareAndCommit(array $previewRows, array $selectedNums, array $editedMap, int $userId): array {
        $rowsToCommit = [];
        $assetService = new AssetService($this->db);

        $count = 0;
        $skipped = 0;
        $errors = [];

        foreach ($previewRows as $row) {
            $rn = strval($row['row_num'] ?? '');
            if (!empty($selectedNums) && !in_array($rn, $selectedNums, true)) continue;

            // Merge frontend edits if user fixed errors manually
            if (isset($editedMap[$rn])) {
                foreach ($editedMap[$rn] as $key => $val) {
                    $row[$key] = $val;
                }
            }

            // Ensure the background ID translation was successful
            if (empty($row['asset_group_id'])) {
                $errors[] = "Row {$rn}: Missing Asset Group ID. Please fix the group name.";
                continue;
            }
            if (empty($row['cost_center_code'])) {
                $errors[] = "Row {$rn}: Missing Cost Center.";
                continue;
            }

            $actualMonths = (int)$row['actual_months'];
            $deprEnd = '';
            if ($actualMonths > 0) {
                $startTs = strtotime($row['depreciation_start_date']);
                $deprEnd = date('Y-m-t', strtotime("+".($actualMonths - 1)." months", strtotime(date('Y-m-01', $startTs))));
            }

            // We only send clean, correct data to AssetService
            $payload = [
                'system_asset_code'       => $row['system_asset_code'],
                'reference_no'            => $row['reference_no'] ?: null,
                'main_zone_code'          => $row['main_zone_code'],
                'zone_code'               => $row['zone_code'],
                'region_code'             => $row['region_code'],
                'cost_center_code'        => $row['cost_center_code'],
                'branch_code'             => $row['branch_code'],
                'branch_name'             => $row['branch_name'],
                'asset_name'              => $row['description'],
                'description'             => $row['description'],
                
                // This correctly links the asset using the integer PK
                'asset_group_id'          => (int)$row['asset_group_id'], 
                
                'months'                  => $actualMonths,
                'serial_number'           => $row['serial_number'] ?: null,
                'item_code'               => $row['item_code'] ?: null,
                'quantity'                => (int)$row['quantity'],
                'property_type'           => $row['property_type'],
                'date_received'           => $row['date_received'],
                'depreciation_start_date' => $row['depreciation_start_date'],
                'depreciation_end_date'   => $deprEnd,
                'depreciation_on'         => 'LAST_DAY',
                'depreciation_day'        => 1,
                'acquisition_cost'        => (float)$row['acquisition_cost'],
                'monthly_depreciation'    => (float)$row['monthly_depreciation'],
                'status'                  => 'ACTIVE',
            ];

            $result = $assetService->createAsset($payload, $userId);

            if ($result['success']) {
                $count++;
            } else {
                if (str_contains($result['error'] ?? '', 'Duplicate')) {
                    $skipped++;
                } else {
                    $errors[] = "Row {$rn}: " . ($result['error'] ?? 'Unknown DB error');
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