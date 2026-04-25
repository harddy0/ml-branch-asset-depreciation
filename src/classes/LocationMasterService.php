<?php
namespace App;

class LocationMasterService {
    private ?\PDO $dbMaster;

    public function __construct(?\PDO $dbMaster) {
        $this->dbMaster = $dbMaster;
    }

    private function checkConnection(): void {
        if (!$this->dbMaster) {
            throw new \Exception("Master Data database connection is not configured or unavailable.");
        }
    }

    // ── 1. Main Zones ─────────────────────────────────────────────────────

    public function getMainZones(): array {
        $this->checkConnection();
        $stmt = $this->dbMaster->query(
            "SELECT DISTINCT main_zone_code FROM main_zone_masterfile WHERE main_zone_code IS NOT NULL ORDER BY main_zone_code ASC"
        );
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ── 2. Sub-Zones (all, or filtered by main_zone_code) ─────────────────

    public function getZones(?string $mainZoneCode = null): array {
        $this->checkConnection();
        $sql    = "SELECT DISTINCT zone_code FROM zone_masterfile WHERE zone_code IS NOT NULL";
        $params = [];
        $sql   .= " ORDER BY zone_code ASC";
        $stmt   = $this->dbMaster->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Cascaded: sub-zones for a given main_zone_code.
     * Falls back to all zones if the join column doesn't exist in the schema.
     */
    public function getZonesByMainZone(string $mainZoneCode): array {
        $this->checkConnection();
        if (trim($mainZoneCode) === '') {
            return $this->getZones();
        }
        try {
            $stmt = $this->dbMaster->prepare(
                "SELECT DISTINCT zone_code
                 FROM zone_masterfile
                 WHERE main_zone_code = :mz AND zone_code IS NOT NULL
                 ORDER BY zone_code ASC"
            );
            $stmt->execute([':mz' => $mainZoneCode]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            // If the column doesn't exist or returns nothing, fall back
            return $rows ?: $this->getZones();
        } catch (\PDOException $e) {
            // Column might not exist — safe fallback
            return $this->getZones();
        }
    }

    // ── 3. Regions (all, or filtered by zone_code) ────────────────────────

    public function getRegions(?string $zoneCode = null): array {
        $this->checkConnection();
        $sql    = "SELECT DISTINCT region_code FROM region_masterfile WHERE region_code IS NOT NULL";
        $params = [];
        $sql   .= " ORDER BY region_code ASC";
        $stmt   = $this->dbMaster->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Cascaded: regions for a given zone_code.
     */
    public function getRegionsByZone(string $zoneCode): array {
        $this->checkConnection();
        if (trim($zoneCode) === '') {
            return $this->getRegions();
        }
        try {
            $stmt = $this->dbMaster->prepare(
                "SELECT DISTINCT r.region_code, r.region_description
                 FROM region_masterfile r
                 WHERE r.zone_code = :zc AND r.region_code IS NOT NULL
                 ORDER BY r.region_code ASC"
            );
            $stmt->execute([':zc' => $zoneCode]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return $rows ?: $this->getRegions();
        } catch (\PDOException $e) {
            return $this->getRegions();
        }
    }

    // ── 4. Branches (all, or filtered by region_code) ─────────────────────

    public function getBranches(?string $regionCode = null): array {
        $this->checkConnection();

        $params = [];
        if ($regionCode) {
            $params[':region_code'] = $regionCode;
        }

        $whereRegion = $regionCode ? " AND b.region_code = :region_code" : "";

        // Preferred query: include branch code when available.
            $sqlWithBranchCode = "SELECT DISTINCT
                        b.cost_center AS cost_center_code,
                    b.code AS branch_code,
                    b.branch_id AS branch_id,
                    b.corporate_name AS corporate_name,
                        b.branch_name,
                        b.region AS region,
                        r.region_code,
                        r.region_description,
                        z.zone_code,
                        m.main_zone_code
                FROM branch_profile b
                LEFT JOIN region_masterfile r ON b.region_code = r.region_code
                LEFT JOIN zone_masterfile z   ON r.zone_code   = z.zone_code
                LEFT JOIN main_zone_masterfile m ON z.main_zone_code = m.main_zone_code
                WHERE b.cost_center IS NOT NULL AND b.branch_name IS NOT NULL"
                . $whereRegion .
                " ORDER BY b.branch_name ASC";

        // Fallback query for schemas without branch_profile.code.
            $sqlFallback = "SELECT DISTINCT
                        b.cost_center AS cost_center_code,
                    '' AS branch_code,
                    '' AS branch_id,
                    '' AS corporate_name,
                        b.branch_name,
                        b.region AS region,
                        r.region_code,
                        r.region_description,
                        z.zone_code,
                        m.main_zone_code
                FROM branch_profile b
                LEFT JOIN region_masterfile r ON b.region_code = r.region_code
                LEFT JOIN zone_masterfile z   ON r.zone_code   = z.zone_code
                LEFT JOIN main_zone_masterfile m ON z.main_zone_code = m.main_zone_code
                WHERE b.cost_center IS NOT NULL AND b.branch_name IS NOT NULL"
                . $whereRegion .
                " ORDER BY b.branch_name ASC";

        try {
            $stmt = $this->dbMaster->prepare($sqlWithBranchCode);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $stmt = $this->dbMaster->prepare($sqlFallback);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
    }

    /**
     * Resolve a provided value to a region code and description.
     * Returns:
     *  - ['code'=>..., 'description'=>...] on single match
     *  - ['ambiguous' => true, 'matches' => [ ['code'=>..., 'description'=>...], ... ]] when multiple description matches
     *  - null when no matches
     */
    public function findByCodeOrDescription(string $value): ?array {
        $this->checkConnection();

        $raw = trim((string)$value);
        if ($raw === '') {
            return null;
        }

        // Try exact code match first
        try {
            $stmtCode = $this->dbMaster->prepare(
                "SELECT region_code, region_description FROM region_masterfile WHERE region_code = :v LIMIT 1"
            );
            $stmtCode->execute([':v' => $raw]);
            $r = $stmtCode->fetch(\PDO::FETCH_ASSOC);
            if ($r && !empty($r['region_code'])) {
                return ['code' => $r['region_code'], 'description' => $r['region_description'] ?? ''];
            }

            // Normalize for description match: collapse whitespace and lowercase
            $norm = mb_strtolower(preg_replace('/\s+/', ' ', $raw));

            $stmtDesc = $this->dbMaster->prepare(
                "SELECT region_code, region_description FROM region_masterfile WHERE LOWER(TRIM(region_description)) = :d"
            );
            $stmtDesc->execute([':d' => trim($norm)]);
            $matches = $stmtDesc->fetchAll(\PDO::FETCH_ASSOC);

            if (!$matches) {
                // Try a looser normalized match using REPLACE of multiple spaces
                $stmtDesc2 = $this->dbMaster->prepare(
                    "SELECT region_code, region_description FROM region_masterfile"
                );
                $stmtDesc2->execute();
                $all = $stmtDesc2->fetchAll(\PDO::FETCH_ASSOC);
                $found = [];
                foreach ($all as $m) {
                    $mnorm = mb_strtolower(preg_replace('/\s+/', ' ', trim($m['region_description'] ?? '')));
                    if ($mnorm === $norm) {
                        $found[] = $m;
                    }
                }
                $matches = $found;
            }

            if (!$matches) {
                return null;
            }

            if (count($matches) === 1) {                      
                $m = $matches[0];
                return ['code' => $m['region_code'], 'description' => $m['region_description'] ?? ''];
            }

            // ambiguous
            $out = ['ambiguous' => true, 'matches' => []];
            foreach ($matches as $m) {
                $out['matches'][] = ['code' => $m['region_code'], 'description' => $m['region_description'] ?? ''];
            }
            return $out;
        } catch (\PDOException $e) {
            return null;
        }
    }

    /**
     * Resolves the best possible location data for the import process.
     * 1. Tries exact Cost Center match.
     * 2. Falls back to fuzzy Branch Name match.
     * 3. Independently fuzzy matches the Region if branch lookup fails.
     */
    public function resolveImportLocation(string $costCenter, string $branchName, string $regionStr): array {
        $this->checkConnection();

        $result = [
            'main_zone_code'   => '',
            'zone_code'        => '',
            'region_code'      => '',
            'branch_name'      => '',
            'cost_center_code' => '',
            'branch_code'      => '',
            'matched_by'       => 'none',
            'errors'           => []
        ];

        $ccClean = trim($costCenter);
        $bnClean = trim($branchName);
        
        // 1. Strict Cost Center Match
        if ($ccClean !== '' && preg_match('/^\d{4}-\d{3}$/', $ccClean)) {
            // Using maa_region to align with the master data structure
            $stmt = $this->dbMaster->prepare(
                "SELECT b.cost_center, b.code as branch_code, b.branch_name, b.region, r.zone_code, z.main_zone_code 
                 FROM branch_profile b
                 LEFT JOIN maa_region r ON b.region = r.region_code OR b.region_code = r.region_code
                 LEFT JOIN zone_masterfile z ON r.zone_code = z.zone_code
                 WHERE b.cost_center = :cc LIMIT 1"
            );
            $stmt->execute([':cc' => $ccClean]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($row) {
                $result['main_zone_code']   = $row['main_zone_code'] ?? '';
                $result['zone_code']        = $row['zone_code'] ?? '';
                $result['region_code']      = $row['region'] ?? '';
                $result['branch_name']      = $row['branch_name'] ?? '';
                $result['cost_center_code'] = $row['cost_center'] ?? '';
                $result['branch_code']      = $row['branch_code'] ?? $row['cost_center'];
                $result['matched_by']       = 'cost_center';
                return $result;
            }
        }

        // 2. Fuzzy Branch Match (If CC fails or is empty)
        if ($bnClean !== '') {
            $stmt = $this->dbMaster->query("SELECT cost_center, code as branch_code, branch_name, region FROM branch_profile");
            $branches = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $bestMatch = null;
            $highestPct = 0;
            // Strip non-alphanumeric characters for a highly forgiving match
            $target = strtolower(preg_replace('/[^a-z0-9]/i', '', $bnClean));

            foreach ($branches as $b) {
                $candidate = strtolower(preg_replace('/[^a-z0-9]/i', '', $b['branch_name']));
                similar_text($target, $candidate, $pct);
                if ($pct > $highestPct) {
                    $highestPct = $pct;
                    $bestMatch = $b;
                }
            }

            // Accept match if it is at least 80% similar
            if ($highestPct >= 80 && $bestMatch) {
                // Fetch hierarchy for the fuzzy matched branch using maa_region
                $stmtH = $this->dbMaster->prepare(
                    "SELECT r.zone_code, z.main_zone_code 
                     FROM maa_region r
                     LEFT JOIN zone_masterfile z ON r.zone_code = z.zone_code
                     WHERE r.region_code = :rc LIMIT 1"
                );
                $stmtH->execute([':rc' => $bestMatch['region']]);
                $hRow = $stmtH->fetch(\PDO::FETCH_ASSOC);

                $result['main_zone_code']   = $hRow['main_zone_code'] ?? '';
                $result['zone_code']        = $hRow['zone_code'] ?? '';
                $result['region_code']      = $bestMatch['region'] ?? '';
                $result['branch_name']      = $bestMatch['branch_name'] ?? '';
                $result['cost_center_code'] = $bestMatch['cost_center'] ?? '';
                $result['branch_code']      = $bestMatch['branch_code'] ?? $bestMatch['cost_center'];
                $result['matched_by']       = 'fuzzy_branch';
                return $result;
            }
        }

        // 3. Independent Fuzzy Region Match (If everything else failed)
        if (trim($regionStr) !== '') {
            $regionMatch = $this->findByCodeOrDescription($regionStr);
            if ($regionMatch && !isset($regionMatch['ambiguous'])) {
                $result['region_code'] = $regionMatch['code'];
                $result['matched_by']  = 'fuzzy_region_only';
            } else {
                $result['errors'][] = "Could not definitively resolve Region '{$regionStr}'.";
            }
        }

        if ($result['matched_by'] === 'none') {
            $result['errors'][] = "Branch/Cost Center could not be verified in Master Data.";
        }

        return $result;
    }

}