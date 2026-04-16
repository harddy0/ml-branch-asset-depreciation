<?php
namespace App;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportService {
    private \PDO  $db;
    private ?\PDO $dbMaster;

    public function __construct(\PDO $db, ?\PDO $dbMaster) {
        $this->db       = $db;
        $this->dbMaster = $dbMaster;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  PREVIEW  — parse & validate only, zero DB writes
    //
    //  Expected 18-column Excel layout (Row 1 = header, Row 2+ = data):
    //   A  Zone
    //   B  Sub-Zone
    //   C  Region
    //   D  Cost Center           (format 0000-000)
    //   E  Branch                (display-only; authoritative from masterdata)
    //   F  Reference Number
    //   G  Serial Number
    //   H  Item Code
    //   I  Asset Group           (must match asset_groups.group_name exactly)
    //   J  Description
    //   K  Date Received
    //   L  Acquisition Cost
    //   M  Cost per Unit
    //   N  Property Type         (PURCHASED / LEASE / LEASEHOLD / MAINTENANCE)
    //   O  Depreciate On         (FIRST_DAY / LAST_DAY / SPECIFIC_DATE)
    //   P  Specific Day          (1-31, only when O = SPECIFIC_DATE)
    //   Q  Quantity
    //   R  Status                (ACTIVE / SOLD / DISPOSED / INACTIVE)
    // ══════════════════════════════════════════════════════════════════════
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

        // ── Pre-fetch asset groups (key: lower group_name → full row) ────
        $groupStmt = $this->db->query("
            SELECT ag.group_code, ag.group_name, ag.actual_months,
                   al.asset_code, al.asset_name,
                   ad.depreciation_code, ad.description AS depreciation_description
            FROM asset_groups ag
            JOIN assets_lookup al ON ag.asset_code = al.asset_code
            JOIN amortization_depreciation ad ON al.depreciation_code = ad.depreciation_code
        ");
        $groups = [];
        while ($g = $groupStmt->fetch(\PDO::FETCH_ASSOC)) {
            $g['actual_months'] = (int)$g['actual_months'];
            $groups[strtolower(trim($g['group_name']))] = $g;
        }

        // ── masterdata lookup ────────────────────────────────────────────
        $masterCheck = $this->dbMaster->prepare(
            "SELECT zone, region, branch_name, code AS branch_code, cost_center
               FROM branch_profile
              WHERE zone = ? AND region = ? AND cost_center = ?
              LIMIT 1"
        );

        // ── pre-load existing system_asset_codes for O(1) dup check ──────
        $existingCodes = [];
        foreach ($this->db->query("SELECT system_asset_code FROM assets") as $r) {
            $existingCodes[strtolower($r['system_asset_code'])] = true;
        }
        $seenInFile = [];

        $allowedPropertyTypes  = ['PURCHASED', 'LEASE', 'LEASEHOLD', 'MAINTENANCE'];
        $allowedDepreciateOn   = ['FIRST_DAY', 'LAST_DAY', 'SPECIFIC_DATE'];
        $allowedStatuses       = ['ACTIVE', 'SOLD', 'DISPOSED', 'INACTIVE'];

        $preview = [];
        $errors  = [];

        foreach ($rows as $index => $row) {
            $rowNum    = $index + 2;
            $rowErrors = [];

            // ── Column mapping ───────────────────────────────────────────
            $zone         = trim((string)($row[0]  ?? ''));
            $zoneCode     = trim((string)($row[1]  ?? ''));
            $regionCode   = trim((string)($row[2]  ?? ''));
            $costCenter   = trim((string)($row[3]  ?? ''));
            $excelBranch  = strtoupper(trim((string)($row[4]  ?? '')));
            $referenceNo  = trim((string)($row[5]  ?? ''));
            $serialNumber = trim((string)($row[6]  ?? ''));
            $itemCode     = trim((string)($row[7]  ?? ''));
            $groupName    = trim((string)($row[8]  ?? ''));
            $description  = trim((string)($row[9]  ?? ''));
            $dateRecVal   = $row[10] ?? null;
            $acqCost      = (float)($row[11] ?? 0);
            $costUnit     = (float)($row[12] ?? 0);
            $propertyType = strtoupper(trim((string)($row[13] ?? 'PURCHASED')));
            $deprOn       = strtoupper(trim((string)($row[14] ?? 'LAST_DAY')));
            $deprDay      = (int)($row[15] ?? 1);
            $quantity     = (int)($row[16] ?? 1);
            $status       = strtoupper(trim((string)($row[17] ?? 'ACTIVE')));

            // ── Normalise optionals ──────────────────────────────────────
            $dbReferenceNo  = $referenceNo  !== '' ? $referenceNo  : null;
            $dbSerialNumber = $serialNumber !== '' ? $serialNumber : null;
            $dbItemCode     = $itemCode     !== '' ? $itemCode     : null;
            if (!in_array($propertyType, $allowedPropertyTypes, true)) $propertyType = 'PURCHASED';
            if (!in_array($deprOn,       $allowedDepreciateOn,  true)) $deprOn       = 'LAST_DAY';
            if (!in_array($status,       $allowedStatuses,      true)) $status       = 'ACTIVE';
            if ($quantity < 1)  $quantity = 1;
            if ($deprDay  < 1 || $deprDay > 31) $deprDay = 1;

            // ── Date parsing ─────────────────────────────────────────────
            $dateReceived = $this->parseDate($dateRecVal);

            // Depreciation start = last day of received month
            $depreciationStartDate = $dateReceived
                ? date('Y-m-t', strtotime($dateReceived))
                : date('Y-m-t');

            // ── Validations ──────────────────────────────────────────────
            if (empty($zone) || empty($costCenter)) {
                $rowErrors[] = "Missing required fields: Zone and Cost Center are required.";
            }

            if (!empty($costCenter) && !preg_match('/^\d{4}-\d{3}$/', $costCenter)) {
                $rowErrors[] = "Invalid Cost Center format ({$costCenter}). Expected 0000-000.";
            }

            if ($acqCost <= 0) {
                $rowErrors[] = "Acquisition Cost must be greater than zero.";
            }

            if (empty($description)) {
                $rowErrors[] = "Description is required.";
            }

            // Group lookup
            $groupEntry = $groups[strtolower($groupName)] ?? null;
            if (!$groupEntry) {
                $rowErrors[] = "Asset Group '{$groupName}' does not exist in the system.";
            }

            // Master data branch validation
            $masterData = null;
            if (empty($rowErrors)) {
                $masterCheck->execute([$zone, $regionCode, $costCenter]);
                $masterData = $masterCheck->fetch(\PDO::FETCH_ASSOC);
                if (!$masterData) {
                    $rowErrors[] = "Branch (Zone:{$zone}, Region:{$regionCode}, Cost Center:{$costCenter}) not found in Master Data.";
                }
            }

            // ── Build base row ───────────────────────────────────────────
            $baseRow = [
                'row_num'                  => $rowNum,
                'has_error'                => !empty($rowErrors),
                // Location
                'main_zone_code'           => $masterData['zone']        ?? $zone,
                'zone_code'                => $zoneCode,
                'region_code'              => $masterData['region']       ?? $regionCode,
                'cost_center_code'         => $masterData['cost_center'] ?? $costCenter,
                'branch_name'              => $masterData['branch_name'] ?? $excelBranch,
                'branch_code'              => $masterData['branch_code'] ?? '',
                // Asset identity
                'reference_no'             => $dbReferenceNo,
                'serial_number'            => $dbSerialNumber,
                'item_code'                => $dbItemCode,
                'group_name'               => $groupEntry['group_name']  ?? $groupName,
                'group_code'               => $groupEntry['group_code']  ?? '',
                'asset_code'               => $groupEntry['asset_code']  ?? '',
                'depreciation_code'        => $groupEntry['depreciation_code'] ?? '',
                'actual_months'            => $groupEntry['actual_months'] ?? 0,
                'description'              => $description,
                // Dates
                'date_received'            => $dateReceived ?? date('Y-m-d'),
                'depreciation_start_date'  => $depreciationStartDate,
                // Financial
                'acquisition_cost'         => $acqCost,
                'cost_unit'                => $costUnit > 0 ? $costUnit : $acqCost,
                'monthly_depreciation'     => ($groupEntry && $groupEntry['actual_months'] > 0)
                                                ? round($acqCost / $groupEntry['actual_months'], 2)
                                                : 0,
                // Settings
                'property_type'            => $propertyType,
                'depreciation_on'          => $deprOn,
                'depreciation_day'         => $deprDay,
                'quantity'                 => $quantity,
                'status'                   => $status,
                // Error tracking
                'errors'                   => $rowErrors,
            ];

            if (!empty($rowErrors)) {
                $errors[]  = "<strong>Row {$rowNum}:</strong> " . implode(' ', $rowErrors);
                $preview[] = $baseRow;
                continue;
            }

            // ── Build system_asset_code ──────────────────────────────────
            $suffix          = $dbReferenceNo ?? strtoupper(substr(uniqid(), -5));
            $systemAssetCode = sprintf("%s-%s-%s-%s",
                $groupEntry['group_code'],
                $masterData['zone'],
                $masterData['branch_code'],
                $suffix
            );

            // ── Duplicate detection ──────────────────────────────────────
            $codeKey     = strtolower($systemAssetCode);
            $isDuplicate = false;

            if (isset($existingCodes[$codeKey])) {
                $rowErrors[] = "Duplicate: System code {$systemAssetCode} already exists in the database.";
                $isDuplicate = true;
            } elseif (isset($seenInFile[$codeKey])) {
                $rowErrors[] = "Duplicate: System code {$systemAssetCode} appears more than once in this file (first seen on row {$seenInFile[$codeKey]}).";
                $isDuplicate = true;
            }

            if ($isDuplicate) {
                $errors[]  = "<strong>Row {$rowNum}:</strong> " . implode(' ', $rowErrors);
                $baseRow['has_error']        = true;
                $baseRow['is_duplicate']     = true;
                $baseRow['system_asset_code'] = $systemAssetCode;
                $baseRow['errors']           = $rowErrors;
                $preview[] = $baseRow;
                continue;
            }

            $seenInFile[$codeKey] = $rowNum;

            $baseRow['system_asset_code'] = $systemAssetCode;
            $baseRow['has_error']         = false;
            $baseRow['errors']            = [];
            $preview[] = $baseRow;
        }

        // ── Build groups map for JS (key: group_code) ────────────────────
        $groupsForJs = [];
        foreach ($groups as $entry) {
            $groupsForJs[$entry['group_code']] = $entry;
        }

        return [
            'success'   => true,
            'preview'   => $preview,
            'errors'    => $errors,
            'hasErrors' => !empty($errors),
            'groups'    => $groupsForJs,   // consumed by JS edit modal GL dropdown
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    //  PREPARE & COMMIT  — merge user edits, delegate to AssetService
    // ══════════════════════════════════════════════════════════════════════
    public function prepareAndCommit(array $previewRows, array $selectedNums, array $editedMap, int $userId): array {
        $rowsToCommit = [];

        foreach ($previewRows as $row) {
            $rn = strval($row['row_num'] ?? '');

            if (!empty($selectedNums) && !in_array($rn, $selectedNums, true)) {
                continue;
            }

            if (!empty($row['has_error'])) {
                continue;
            }

            // Merge user edits
            if (isset($editedMap[$rn])) {
                $edited = $editedMap[$rn];

                $editableFields = [
                    'reference_no', 'serial_number', 'item_code', 'description',
                    'date_received', 'depreciation_start_date',
                    'acquisition_cost', 'cost_unit', 'monthly_depreciation',
                    'group_name', 'group_code', 'asset_code', 'depreciation_code', 'actual_months',
                    'property_type', 'depreciation_on', 'depreciation_day',
                    'quantity', 'status',
                    'main_zone_code', 'zone_code', 'region_code', 'cost_center_code',
                    'branch_name', 'branch_code',
                ];

                foreach ($editableFields as $field) {
                    if (array_key_exists($field, $edited)) {
                        $row[$field] = $edited[$field];
                    }
                }

                // Rebuild system_asset_code from updated parts
                $suffix = !empty($row['reference_no'])
                    ? $row['reference_no']
                    : strtoupper(substr(uniqid(), -5));

                $row['system_asset_code'] = sprintf(
                    "%s-%s-%s-%s",
                    $row['group_code'],
                    $row['main_zone_code'],
                    $row['branch_code'] ?? '',
                    $suffix
                );
            }

            $rowsToCommit[] = $row;
        }

        if (empty($rowsToCommit)) {
            return ['success' => false, 'error' => 'No valid rows were selected for import.'];
        }

        return $this->commitImport($rowsToCommit, $userId);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  COMMIT  — delegates each row to AssetService::createAsset()
    //            which handles assets + running_depreciation + ledger
    // ══════════════════════════════════════════════════════════════════════
    public function commitImport(array $previewRows, int $userId): array {
        $assetService = new AssetService($this->db);

        // Hard-guard: pre-check for duplicates
        $dupCheck = $this->db->prepare("SELECT COUNT(*) FROM assets WHERE system_asset_code = ?");

        $count   = 0;
        $skipped = 0;
        $errors  = [];

        foreach ($previewRows as $r) {
            $dupCheck->execute([$r['system_asset_code']]);
            if ((int)$dupCheck->fetchColumn() > 0) {
                $skipped++;
                continue;
            }

            // Map preview row → AssetService::createAsset() payload
            $actualMonths = (int)($r['actual_months'] ?? 0);
            $acqCost      = (float)($r['acquisition_cost'] ?? 0);
            $monthlyDep   = ($actualMonths > 0 && $acqCost > 0)
                ? round($acqCost / $actualMonths, 2)
                : (float)($r['monthly_depreciation'] ?? 0);

            // depreciation_end_date = start + actual_months - 1 month
            $endDate = '';
            if (!empty($r['depreciation_start_date']) && $actualMonths > 0) {
                $endDate = date(
                    'Y-m-d',
                    strtotime($r['depreciation_start_date'] . ' +' . ($actualMonths - 1) . ' months')
                );
                // Snap to last day of that month
                $endDate = date('Y-m-t', strtotime($endDate));
            }

            $payload = [
                'system_asset_code'       => $r['system_asset_code'],
                'reference_no'            => $r['reference_no']     ?? null,
                'main_zone_code'          => $r['main_zone_code']   ?? '',
                'zone_code'               => $r['zone_code']        ?? '',
                'region_code'             => $r['region_code']      ?? '',
                'cost_center_code'        => $r['cost_center_code'] ?? '',
                'branch_name'             => $r['branch_name']      ?? '',
                'group_code'              => $r['group_code']       ?? '',
                'asset_code'              => $r['asset_code']       ?? '',
                'depreciation_code'       => $r['depreciation_code'] ?? '',
                'description'             => $r['description']      ?? '',
                'serial_number'           => $r['serial_number']    ?? null,
                'item_code'               => $r['item_code']        ?? null,
                'quantity'                => (int)($r['quantity']   ?? 1),
                'property_type'           => $r['property_type']    ?? 'PURCHASED',
                'date_received'           => $r['date_received']    ?? date('Y-m-d'),
                'depreciation_start_date' => $r['depreciation_start_date'] ?? date('Y-m-t'),
                'depreciation_end_date'   => $endDate,
                'depreciation_on'         => $r['depreciation_on']  ?? 'LAST_DAY',
                'depreciation_day'        => (int)($r['depreciation_day'] ?? 1),
                'acquisition_cost'        => $acqCost,
                'cost_unit'               => (float)($r['cost_unit'] ?? $acqCost),
                'monthly_depreciation'    => $monthlyDep,
                'status'                  => $r['status']           ?? 'ACTIVE',
            ];

            $result = $assetService->createAsset($payload, $userId);

            if ($result['success']) {
                $count++;
            } else {
                $errors[] = "Row {$r['row_num']}: " . ($result['error'] ?? 'Unknown error');
            }
        }

        if ($count === 0 && !empty($errors)) {
            return ['success' => false, 'error' => implode('; ', $errors)];
        }

        return [
            'success' => true,
            'count'   => $count,
            'skipped' => $skipped,
            'errors'  => $errors,
        ];
    }
}