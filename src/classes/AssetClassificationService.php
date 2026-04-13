<?php
namespace App;

class AssetClassificationService {
    private \PDO $db;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }

    /**
     * ==========================================
     * UI HELPER METHODS (For Asset Creation Form)
     * ==========================================
     */

    public function getDropdownOptions(): array {
        $sql = "SELECT group_code, group_name FROM asset_groups ORDER BY group_name ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * The "Magic" Fetcher - NOW WITH DESCRIPTIONS
     * Returns the codes for the database, and the names/descriptions for the UI.
     */
    public function getGroupDetailsForAssetCreation(string $groupCode): ?array {
        $sql = "
            SELECT 
                ag.group_code,
                ag.group_name,
                ag.actual_months,
                al.asset_code,
                al.asset_name,
                ad.depreciation_code,
                ad.description AS depreciation_description
            FROM asset_groups ag
            JOIN assets_lookup al ON ag.asset_code = al.asset_code
            JOIN amortization_depreciation ad ON al.depreciation_code = ad.depreciation_code
            WHERE ag.group_code = :group_code
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':group_code' => $groupCode]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * ==========================================
     * CRUD: amortization_depreciation (The P&L Rules)
     * ==========================================
     */
    public function createAmortizationRule(array $data): array {
        $sql = "INSERT INTO amortization_depreciation (depreciation_code, description, limit_months, rule_type) 
                VALUES (:code, :desc, :months, :type)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':code'   => $data['depreciation_code'],
                ':desc'   => $data['description'],
                ':months' => $data['limit_months'],
                ':type'   => $data['rule_type']
            ]);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAllAmortizationRules(): array {
        return $this->db->query("SELECT * FROM amortization_depreciation ORDER BY depreciation_code")->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * ==========================================
     * CRUD: assets_lookup (The Asset Categories)
     * ==========================================
     */
    public function createAssetLookup(array $data): array {
        $sql = "INSERT INTO assets_lookup (asset_code, asset_name, depreciation_code) 
                VALUES (:asset_code, :asset_name, :depreciation_code)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':asset_code'        => $data['asset_code'],
                ':asset_name'        => $data['asset_name'],
                ':depreciation_code' => $data['depreciation_code']
            ]);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAllAssetLookups(): array {
        $sql = "SELECT al.*, ad.description as depreciation_description 
                FROM assets_lookup al
                JOIN amortization_depreciation ad ON al.depreciation_code = ad.depreciation_code
                ORDER BY al.asset_code";
        return $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * ==========================================
     * CRUD: asset_groups (The UI Dropdowns)
     * ==========================================
     */
    public function createAssetGroup(array $data): array {
        $sql = "INSERT INTO asset_groups (group_code, group_name, actual_months, asset_code) 
                VALUES (:group_code, :group_name, :actual_months, :asset_code)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':group_code'    => $data['group_code'],
                ':group_name'    => $data['group_name'],
                ':actual_months' => $data['actual_months'],
                ':asset_code'    => $data['asset_code']
            ]);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAllAssetGroups(): array {
        // Also pulling the parent descriptions here so the Admin data table is comprehensive
        $sql = "SELECT ag.*, al.asset_name, ad.depreciation_code, ad.description AS depreciation_description
                FROM asset_groups ag
                JOIN assets_lookup al ON ag.asset_code = al.asset_code
                JOIN amortization_depreciation ad ON al.depreciation_code = ad.depreciation_code
                ORDER BY ag.group_name";
        return $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
}