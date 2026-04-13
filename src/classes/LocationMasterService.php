<?php
namespace App;

class LocationMasterService {
    private ?\PDO $dbMaster;

    /**
     * Inject the secondary database connection (DB2 / Master Data)
     */
    public function __construct(?\PDO $dbMaster) {
        $this->dbMaster = $dbMaster;
    }

    /**
     * Helper to check if DB2 is connected
     */
    private function checkConnection(): void {
        if (!$this->dbMaster) {
            throw new \Exception("Master Data database connection is not configured or unavailable.");
        }
    }

    /**
     * 1. Fetch Main Zones
     * Table: main_zone_masterfile
     */
    public function getMainZones(): array {
        $this->checkConnection();
        $sql = "SELECT DISTINCT main_zone_code FROM main_zone_masterfile WHERE main_zone_code IS NOT NULL ORDER BY main_zone_code ASC";
        $stmt = $this->dbMaster->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 2. Fetch Sub-Zones
     * Table: zone_masterfile
     * Note: Added an optional $mainZoneCode parameter. If your DB2 links these tables, 
     * you can filter Zones by the Main Zone selected by the user.
     */
    public function getZones(?string $mainZoneCode = null): array {
        $this->checkConnection();
        
        $sql = "SELECT DISTINCT zone_code FROM zone_masterfile WHERE zone_code IS NOT NULL";
        $params = [];

        // If zone_masterfile has a main_zone_code column to link them, uncomment this logic:
        /*
        if ($mainZoneCode) {
            $sql .= " AND main_zone_code = :main_zone_code";
            $params[':main_zone_code'] = $mainZoneCode;
        }
        */

        $sql .= " ORDER BY zone_code ASC";
        
        $stmt = $this->dbMaster->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 3. Fetch Regions
     * Table: region_masterfile
     */
    public function getRegions(?string $zoneCode = null): array {
        $this->checkConnection();
        
        $sql = "SELECT DISTINCT region_code FROM region_masterfile WHERE region_code IS NOT NULL";
        $params = [];

        // If region_masterfile has a zone_code column linking them, you can filter it:
        /*
        if ($zoneCode) {
            $sql .= " AND zone_code = :zone_code";
            $params[':zone_code'] = $zoneCode;
        }
        */

        $sql .= " ORDER BY region_code ASC";
        
        $stmt = $this->dbMaster->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 4. Fetch Branches / Cost Centers
     * Table: branch_profile
     * Returns both cost_center and branch_name together.
     */
    public function getBranches(?string $regionCode = null): array {
        $this->checkConnection();
        
        $sql = "SELECT DISTINCT cost_center AS cost_center_code, branch_name 
                FROM branch_profile 
                WHERE cost_center IS NOT NULL AND branch_name IS NOT NULL";
        $params = [];

        // If branch_profile has a region_code column linking them, you can filter it:
        /*
        if ($regionCode) {
            $sql .= " AND region_code = :region_code";
            $params[':region_code'] = $regionCode;
        }
        */

        $sql .= " ORDER BY branch_name ASC";
        
        $stmt = $this->dbMaster->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}