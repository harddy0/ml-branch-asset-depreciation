<?php
namespace App;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportService {
    private \PDO  $db;
    private ?\PDO $dbMaster;

    public function __construct(\PDO $db, ?\PDO $dbMaster) {
        $this->db       = $db;
        $this->dbMaster = $dbMaster;
    }

    /**
     * Normalizes Excel/CSV date inputs into Y-m-d or null.
     */
    private function parseDate($value): ?string {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float)$value)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $text = trim((string)$value);
        if ($text === '') {
            return null;
        }

        $formats = [
            'Y-m-d', 'Y/m/d',
            'm/d/Y', 'd/m/Y',
            'm-d-Y', 'd-m-Y',
            'Y-m-d H:i:s', 'Y/m/d H:i:s',
            'm/d/Y H:i:s', 'd/m/Y H:i:s',
        ];

        foreach ($formats as $format) {
            $dt = \DateTime::createFromFormat($format, $text);
            if ($dt instanceof \DateTime) {
                return $dt->format('Y-m-d');
            }
        }

        $ts = strtotime($text);
        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    /**
     * Normalize numeric input (remove thousands separators/currency symbols)
     */
    private function normalizeNumber($value): float {
        $s = trim((string)($value ?? ''));
        if ($s === '') return 0.0;
        $s = str_replace([',', ' ', '₱', '$'], '', $s);
        $s = preg_replace('/[^0-9.\-]/', '', $s);
        if ($s === '' || $s === '.' || $s === '-') return 0.0;
        return (float)$s;
    }

    /**
     * Computes depreciation start date from date received and schedule setting.
     */
    private function computeDepreciationStartDate(?string $dateReceived, string $deprOn, int $deprDay): string {
        $base = $dateReceived ?: date('Y-m-d');
        $ts = strtotime($base);
        if ($ts === false) {
            $ts = time();
        }

        if ($deprOn === 'FIRST_DAY') {
            return date('Y-m-01', $ts);
        }

        if ($deprOn === 'SPECIFIC_DATE') {
            $year = (int)date('Y', $ts);
            $month = (int)date('m', $ts);
            $lastDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $day = max(1, min($deprDay, $lastDay));
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        return date('Y-m-t', $ts);
    }

    /**
     * Computes depreciation end date using the same depreciation_on semantics
     * as manual add (LAST_DAY/FIRST_DAY/SPECIFIC_DATE).
     */
    private function computeDepreciationEndDate(?string $startDate, int $actualMonths, string $deprOn, int $deprDay): string {
        if (empty($startDate) || $actualMonths <= 0) {
            return '';
        }

        $startTs = strtotime($startDate);
        if ($startTs === false) {
            return '';
        }

        $targetTs = strtotime(date('Y-m-01', $startTs) . ' +' . ($actualMonths - 1) . ' months');
        if ($targetTs === false) {
            return '';
        }

        $year = (int)date('Y', $targetTs);
        $month = (int)date('m', $targetTs);

        if ($deprOn === 'FIRST_DAY') {
            return sprintf('%04d-%02d-01', $year, $month);
        }

        if ($deprOn === 'SPECIFIC_DATE') {
            $lastDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $day = max(1, min($deprDay, $lastDay));
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        return date('Y-m-t', $targetTs);
    }

    /**
     * Normalizes group keys so parser matching tolerates case and spacing variance.
     */
    private function normalizeGroupKey(string $value): string {
        $text = str_replace("\xC2\xA0", ' ', $value);
        $text = preg_replace('/\s+/', ' ', trim($text));
        return strtolower((string)$text);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  PREVIEW  — parse & validate only, zero DB writes
    //
    //  Expected 18-column Excel layout (Row 1 = header, Row 2+ = data):
    //   A  Serial Number
    //   B  Asset Description
    //   C  Reference Number      (optional)
    //   D  Quantity
    //   E  Property Type         (PURCHASED / LEASE / LEASEHOLD / MAINTENANCE)
    //   F  Asset Group           (group_name or group_code)
    //   G  Acquisition Cost
    //   H  Date Received
    //   I  Main Zone
    //   J  Sub-Zone
    //   K  Region
    //   L  Cost Center           (format 0000-000)
    //   M  Branch                (display-only; authoritative from masterdata)
    //   N  Item Code             (optional)
    //   O  Cost Unit             (optional)
    //   P  Depreciation Start Date (optional)
    //   Q  Depreciation On       (FIRST_DAY / LAST_DAY / SPECIFIC_DATE)
    //   R  Depreciation Day      (1-31, only when Q = SPECIFIC_DATE)
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
        $groupsByName = [];
        $groupsByCode = [];
        while ($g = $groupStmt->fetch(\PDO::FETCH_ASSOC)) {
            $g['actual_months'] = (int)$g['actual_months'];
            $nameKey = $this->normalizeGroupKey((string)$g['group_name']);
            $codeKey = strtoupper(trim((string)$g['group_code']));

            if ($nameKey !== '') {
                $groupsByName[$nameKey] = $g;
            }
            if ($codeKey !== '') {
                $groupsByCode[$codeKey] = $g;
            }
        }

        // ── masterdata lookup ────────────────────────────────────────────
        $masterCheck = $this->dbMaster->prepare(
            "SELECT zone, region, branch_name, code AS branch_code, cost_center
               FROM branch_profile
              WHERE zone = ? AND region = ? AND cost_center = ?
              LIMIT 1"
        );
        $masterCheckByCostCenter = $this->dbMaster->prepare(
            "SELECT zone, region, branch_name, code AS branch_code, cost_center
               FROM branch_profile
              WHERE cost_center = ?
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
            $serialNumber = trim((string)($row[0]  ?? ''));
            $description  = trim((string)($row[1]  ?? ''));
            $referenceNo  = trim((string)($row[2]  ?? ''));
            $quantity     = (int)($row[3] ?? 1);
            $propertyType = strtoupper(trim((string)($row[4]  ?? 'PURCHASED')));
            $groupRaw     = trim((string)($row[5]  ?? ''));
            $acqCost      = $this->normalizeNumber($row[6] ?? 0);
            $dateRecVal   = $row[7] ?? null;
            $zone         = trim((string)($row[8]  ?? ''));  // main zone
            $zoneCode     = trim((string)($row[9]  ?? ''));  // sub-zone
            $regionCode   = trim((string)($row[10] ?? ''));
            $costCenter   = trim((string)($row[11] ?? ''));
            $excelBranch  = strtoupper(trim((string)($row[12] ?? '')));
            $itemCode     = trim((string)($row[13] ?? ''));
            // cost_unit column removed from import format; ignore column 15 if present
            $deprStartVal = $row[15] ?? null;
            $deprOn       = strtoupper(trim((string)($row[16] ?? 'LAST_DAY')));
            $deprDay      = (int)($row[17] ?? 1);
            $status       = 'ACTIVE';

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
            $deprStartFromExcel = $this->parseDate($deprStartVal);

            $depreciationStartDate = $deprStartFromExcel
                ?: $this->computeDepreciationStartDate($dateReceived, $deprOn, $deprDay);

            // ── Validations ──────────────────────────────────────────────
            if (empty($costCenter)) {
                $rowErrors[] = "Missing required field: Cost Center is required.";
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
            $groupEntry = null;
            $groupNameKey = $this->normalizeGroupKey($groupRaw);
            $groupCodeKey = strtoupper($groupRaw);

            if ($groupCodeKey !== '' && isset($groupsByCode[$groupCodeKey])) {
                $groupEntry = $groupsByCode[$groupCodeKey];
            }

            if (!$groupEntry && $groupNameKey !== '' && isset($groupsByName[$groupNameKey])) {
                $groupEntry = $groupsByName[$groupNameKey];
            }

            if (!$groupEntry && preg_match('/^([A-Za-z0-9_\-\/]+)\s*[-:]\s*/', $groupRaw, $m)) {
                $prefixCode = strtoupper(trim($m[1]));
                if (isset($groupsByCode[$prefixCode])) {
                    $groupEntry = $groupsByCode[$prefixCode];
                }
            }

            if (!$groupEntry) {
                $rowErrors[] = "Asset Group '{$groupRaw}' does not exist in the system.";
            }

            // Master data branch validation — resolve region description/code before lookup
            $masterData = null;
            if (empty($rowErrors)) {
                $resolvedRegionCode = $regionCode;
                if ($this->dbMaster) {
                    $locSvc = new LocationMasterService($this->dbMaster);
                    try {
                        $resolved = $locSvc->findByCodeOrDescription($regionCode);
                        if ($resolved === null) {
                            // No match: mark unresolved so we don't write descriptions into code fields
                            $resolvedRegionCode = null;
                            $rowErrors[] = "Unresolved Region '{$regionCode}'";
                        } elseif (is_array($resolved)) {
                            if (isset($resolved['ambiguous']) && $resolved['ambiguous']) {
                                $suggests = array_map(function($m){ return ($m['code'] ?? '') . ' (' . ($m['description'] ?? '') . ')'; }, $resolved['matches']);
                                $rowErrors[] = "Ambiguous Region '{$regionCode}': " . implode(', ', $suggests);
                                $resolvedRegionCode = null;
                            } elseif (isset($resolved['code'])) {
                                $resolvedRegionCode = $resolved['code'];
                            }
                        }
                    } catch (\Throwable $e) {
                        // On error, mark unresolved to be safe
                        $resolvedRegionCode = null;
                        $rowErrors[] = "Region resolution error for '{$regionCode}'";
                    }
                }

                $masterCheck->execute([$zone, $resolvedRegionCode, $costCenter]);
                $masterData = $masterCheck->fetch(\PDO::FETCH_ASSOC);

                if (!$masterData && !empty($costCenter)) {
                    $masterCheckByCostCenter->execute([$costCenter]);
                    $masterData = $masterCheckByCostCenter->fetch(\PDO::FETCH_ASSOC);
                }

                if (!$masterData) {
                    $rowErrors[] = "Branch (Zone:{$zone}, Region:{$resolvedRegionCode}, Cost Center:{$costCenter}) not found in Master Data.";
                }
            }

            $zonePart = $zoneCode !== ''
                ? $zoneCode
                : ($masterData['zone'] ?? $zone);
            $branchPart = $masterData['branch_code'] ?? $costCenter;

            // ── Build base row ───────────────────────────────────────────
            $baseRow = [
                'row_num'                  => $rowNum,
                'has_error'                => !empty($rowErrors),
                // Location
                'main_zone_code'           => $masterData['zone']        ?? $zone,
                'zone_code'                => $zoneCode,
                'region_code'              => $masterData['region']       ?? ($resolvedRegionCode ?? null),
                'cost_center_code'         => $masterData['cost_center'] ?? $costCenter,
                'branch_name'              => $masterData['branch_name'] ?? $excelBranch,
                'branch_code'              => $masterData['branch_code'] ?? $costCenter,
                // Asset identity
                'reference_no'             => $dbReferenceNo,
                'serial_number'            => $dbSerialNumber,
                'item_code'                => $dbItemCode,
                'group_name'               => $groupEntry['group_name']  ?? $groupRaw,
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
                $groupEntry['asset_code'],
                $zonePart,
                $branchPart,
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
        foreach ($groupsByCode as $entry) {
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

        $allowedPropertyTypes = ['PURCHASED', 'LEASE', 'LEASEHOLD', 'MAINTENANCE'];
        $allowedDepreciateOn  = ['FIRST_DAY', 'LAST_DAY', 'SPECIFIC_DATE'];
        $allowedStatuses      = ['ACTIVE', 'SOLD', 'DISPOSED', 'INACTIVE'];

        // Re-load authoritative group mapping for commit-time hardening.
        $groupStmt = $this->db->query("\n            SELECT ag.group_code, ag.group_name, ag.actual_months,\n                   al.asset_code, ad.depreciation_code\n            FROM asset_groups ag\n            JOIN assets_lookup al ON ag.asset_code = al.asset_code\n            JOIN amortization_depreciation ad ON al.depreciation_code = ad.depreciation_code\n        ");
        $groupsByCode = [];
        while ($g = $groupStmt->fetch(\PDO::FETCH_ASSOC)) {
            $g['actual_months'] = (int)$g['actual_months'];
            $groupsByCode[$g['group_code']] = $g;
        }

        // Pre-load existing codes to prevent crafted requests from bypassing row flags.
        $existingCodes = [];
        foreach ($this->db->query("SELECT system_asset_code FROM assets") as $r) {
            $existingCodes[strtolower((string)$r['system_asset_code'])] = true;
        }
        $seenCodes = [];

        foreach ($previewRows as $row) {
            $rn = strval($row['row_num'] ?? '');

            if (!empty($selectedNums) && !in_array($rn, $selectedNums, true)) {
                continue;
            }

            // Merge user edits
            if (isset($editedMap[$rn])) {
                $edited = $editedMap[$rn];

                $editableFields = [
                    'reference_no', 'serial_number', 'item_code', 'description',
                    'date_received', 'depreciation_start_date',
                    'acquisition_cost', 'monthly_depreciation',
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
                    $row['asset_code'],
                    $row['zone_code'] ?: ($row['main_zone_code'] ?? ''),
                    $row['branch_code'] ?: ($row['cost_center_code'] ?? ''),
                    $suffix
                );
            }

            // Commit-time hardening: sanitize and fully re-validate on server,
            // instead of trusting client-provided row flags.
            $row['reference_no'] = isset($row['reference_no']) ? trim((string)$row['reference_no']) : '';
            $row['serial_number'] = isset($row['serial_number']) ? trim((string)$row['serial_number']) : '';
            $row['item_code'] = isset($row['item_code']) ? trim((string)$row['item_code']) : '';
            $row['description'] = trim((string)($row['description'] ?? ''));
            $row['main_zone_code'] = trim((string)($row['main_zone_code'] ?? ''));
            $row['zone_code'] = trim((string)($row['zone_code'] ?? ''));
            $row['region_code'] = trim((string)($row['region_code'] ?? ''));
            $row['cost_center_code'] = trim((string)($row['cost_center_code'] ?? ''));
            $row['branch_name'] = trim((string)($row['branch_name'] ?? ''));
            $row['branch_code'] = trim((string)($row['branch_code'] ?? ''));
            $row['group_code'] = trim((string)($row['group_code'] ?? ''));

            if ($row['description'] === '' || $row['cost_center_code'] === '') {
                continue;
            }
            if (!preg_match('/^\d{4}-\d{3}$/', $row['cost_center_code'])) {
                continue;
            }

            if ($row['branch_code'] === '') {
                $row['branch_code'] = $row['cost_center_code'];
            }

            $zonePart = $row['zone_code'] !== ''
                ? $row['zone_code']
                : $row['main_zone_code'];

            if ($zonePart === '') {
                continue;
            }

            $dateReceived = $this->parseDate($row['date_received'] ?? null) ?: date('Y-m-d');
            $deprOn = strtoupper(trim((string)($row['depreciation_on'] ?? 'LAST_DAY')));
            if (!in_array($deprOn, $allowedDepreciateOn, true)) {
                $deprOn = 'LAST_DAY';
            }
            $deprDay = (int)($row['depreciation_day'] ?? 1);
            if ($deprDay < 1 || $deprDay > 31) {
                $deprDay = 1;
            }

            $deprStart = $this->parseDate($row['depreciation_start_date'] ?? null)
                ?: $this->computeDepreciationStartDate($dateReceived, $deprOn, $deprDay);

            $propertyType = strtoupper(trim((string)($row['property_type'] ?? 'PURCHASED')));
            if (!in_array($propertyType, $allowedPropertyTypes, true)) {
                $propertyType = 'PURCHASED';
            }

            $status = strtoupper(trim((string)($row['status'] ?? 'ACTIVE')));
            if (!in_array($status, $allowedStatuses, true)) {
                $status = 'ACTIVE';
            }

            $quantity = (int)($row['quantity'] ?? 1);
            if ($quantity < 1) {
                $quantity = 1;
            }

            $acqCost = $this->normalizeNumber($row['acquisition_cost'] ?? 0);
            if ($acqCost <= 0) {
                continue;
            }

            if ($row['group_code'] === '' || !isset($groupsByCode[$row['group_code']])) {
                continue;
            }

            $group = $groupsByCode[$row['group_code']];
            $actualMonths = (int)$group['actual_months'];

            $row['group_name'] = $group['group_name'];
            $row['asset_code'] = $group['asset_code'];
            $row['depreciation_code'] = $group['depreciation_code'];
            $row['actual_months'] = $actualMonths;
            $row['date_received'] = $dateReceived;
            $row['depreciation_start_date'] = $deprStart;
            $row['depreciation_on'] = $deprOn;
            $row['depreciation_day'] = $deprDay;
            $row['property_type'] = $propertyType;
            $row['status'] = $status;
            $row['quantity'] = $quantity;
            $row['acquisition_cost'] = $acqCost;

            $row['monthly_depreciation'] = ($actualMonths > 0)
                ? round($acqCost / $actualMonths, 2)
                : 0;

            $suffix = $row['reference_no'] !== ''
                ? $row['reference_no']
                : strtoupper(substr(uniqid(), -5));

            $row['system_asset_code'] = sprintf(
                "%s-%s-%s-%s",
                $row['asset_code'],
                $zonePart,
                $row['branch_code'],
                $suffix
            );

            $codeKey = strtolower($row['system_asset_code']);
            if (isset($existingCodes[$codeKey]) || isset($seenCodes[$codeKey])) {
                continue;
            }
            $seenCodes[$codeKey] = true;

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
            $acqCost      = $this->normalizeNumber($r['acquisition_cost'] ?? 0);
            $monthlyDep   = ($actualMonths > 0 && $acqCost > 0)
                ? round($acqCost / $actualMonths, 2)
                : (float)($r['monthly_depreciation'] ?? 0);

            $deprOn  = strtoupper(trim((string)($r['depreciation_on'] ?? 'LAST_DAY')));
            $deprDay = (int)($r['depreciation_day'] ?? 1);

            $endDate = $this->computeDepreciationEndDate(
                $r['depreciation_start_date'] ?? null,
                $actualMonths,
                $deprOn,
                $deprDay
            );

            // Ensure region_code is canonical: resolve description -> code if needed
            $finalRegionCode = '';
            if (!empty($r['region_code'])) {
                if ($this->dbMaster) {
                    try {
                        $locSvc = new LocationMasterService($this->dbMaster);
                        $resolved = $locSvc->findByCodeOrDescription($r['region_code']);
                        if (is_array($resolved) && isset($resolved['code'])) {
                            $finalRegionCode = $resolved['code'];
                        } else {
                            // ambiguous or not found -> leave empty to cause AssetService to validate/fail
                            $finalRegionCode = '';
                        }
                    } catch (\Throwable $e) {
                        $finalRegionCode = '';
                    }
                } else {
                    // No master DB available; pass through value (risky)
                    $finalRegionCode = $r['region_code'];
                }
            }

            $payload = [
                'system_asset_code'       => $r['system_asset_code'],
                'reference_no'            => $r['reference_no']     ?? null,
                'main_zone_code'          => $r['main_zone_code']   ?? '',
                'zone_code'               => $r['zone_code']        ?? '',
                'region_code'             => $finalRegionCode       ?? '',
                'cost_center_code'        => $r['cost_center_code'] ?? '',
                'branch_name'             => $r['branch_name']      ?? '',
                'asset_name'              => $r['asset_name']       ?? ($r['description'] ?? ''),
                'months'                  => $actualMonths,
                'group_code'              => $r['group_code']       ?? '',
                'asset_code'              => $r['asset_code']       ?? '',
                'depreciation_code'       => $r['depreciation_code'] ?? '',
                'item_gl_code'            => $r['item_gl_code']     ?? ($r['asset_code'] ?? ''),
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