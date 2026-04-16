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
                    b.branch_name,
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
                    b.branch_name,
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
}