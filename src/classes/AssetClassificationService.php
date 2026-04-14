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

    private function extractDbErrorMessage(\PDOException $e): string {
        return $e->errorInfo[2] ?? $e->getMessage();
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
        } catch (\PDOException $e) {
            return ['success' => false, 'error' => $this->extractDbErrorMessage($e)];
        }
    }

    public function getAllAmortizationRules(): array {
        return $this->db->query("SELECT * FROM amortization_depreciation ORDER BY depreciation_code ASC")
                        ->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function updateAmortizationRule(string $originalCode, array $data): array {
        $existsStmt = $this->db->prepare("SELECT COUNT(*) FROM amortization_depreciation WHERE depreciation_code = :code");
        $existsStmt->execute([':code' => $originalCode]);
        if ((int)$existsStmt->fetchColumn() < 1) {
            return ['success' => false, 'error' => 'P&L rule not found.'];
        }

        $sql = "UPDATE amortization_depreciation
                SET depreciation_code = :new_code,
                    description       = :description,
                    limit_months      = :limit_months,
                    rule_type         = :rule_type
                WHERE depreciation_code = :original_code";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':new_code'      => $data['depreciation_code'],
                ':description'   => $data['description'],
                ':limit_months'  => $data['limit_months'],
                ':rule_type'     => $data['rule_type'],
                ':original_code' => $originalCode,
            ]);

            return ['success' => true];
        } catch (\PDOException $e) {
            return ['success' => false, 'error' => $this->extractDbErrorMessage($e)];
        }
    }

    public function deleteAmortizationRule(string $depreciationCode): array {
        $sql = "DELETE FROM amortization_depreciation WHERE depreciation_code = :depreciation_code";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':depreciation_code' => $depreciationCode]);

            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'error' => 'P&L rule not found.'];
            }

            return ['success' => true];
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return [
                    'success' => false,
                    'error'   => 'Cannot delete this P&L rule because it is used by one or more asset types.',
                ];
            }

            return ['success' => false, 'error' => $this->extractDbErrorMessage($e)];
        }
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
        } catch (\PDOException $e) {
            return ['success' => false, 'error' => $this->extractDbErrorMessage($e)];
        }
    }

    public function getAllAssetLookups(): array {
        $sql = "SELECT al.*, ad.description as depreciation_description
                FROM assets_lookup al
                JOIN amortization_depreciation ad ON al.depreciation_code = ad.depreciation_code
                ORDER BY al.asset_code ASC";
        return $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function updateAssetLookup(string $originalAssetCode, array $data): array {
        $existsStmt = $this->db->prepare("SELECT COUNT(*) FROM assets_lookup WHERE asset_code = :asset_code");
        $existsStmt->execute([':asset_code' => $originalAssetCode]);
        if ((int)$existsStmt->fetchColumn() < 1) {
            return ['success' => false, 'error' => 'Asset type not found.'];
        }

        $sql = "UPDATE assets_lookup
                SET asset_code         = :new_asset_code,
                    asset_name         = :asset_name,
                    depreciation_code  = :depreciation_code
                WHERE asset_code = :original_asset_code";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':new_asset_code'     => $data['asset_code'],
                ':asset_name'         => $data['asset_name'],
                ':depreciation_code'  => $data['depreciation_code'],
                ':original_asset_code'=> $originalAssetCode,
            ]);

            return ['success' => true];
        } catch (\PDOException $e) {
            return ['success' => false, 'error' => $this->extractDbErrorMessage($e)];
        }
    }

    public function deleteAssetLookup(string $assetCode): array {
        $sql = "DELETE FROM assets_lookup WHERE asset_code = :asset_code";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':asset_code' => $assetCode]);

            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'error' => 'Asset type not found.'];
            }

            return ['success' => true];
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return [
                    'success' => false,
                    'error'   => 'Cannot delete this asset type because it is used by one or more asset groups or assets.',
                ];
            }

            return ['success' => false, 'error' => $this->extractDbErrorMessage($e)];
        }
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
        } catch (\PDOException $e) {
            if ($e->getCode() === '45000') {
                return ['success' => false, 'error' => $this->extractDbErrorMessage($e)];
            }

            return ['success' => false, 'error' => $this->extractDbErrorMessage($e)];
        }
    }

    public function getAllAssetGroups(): array {
        $sql = "SELECT ag.*, al.asset_name, ad.depreciation_code, ad.description AS depreciation_description
                FROM asset_groups ag
                JOIN assets_lookup al ON ag.asset_code = al.asset_code
                JOIN amortization_depreciation ad ON al.depreciation_code = ad.depreciation_code
                ORDER BY ag.group_name ASC";
        return $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function updateAssetGroup(int $id, array $data): array {
        $existsStmt = $this->db->prepare("SELECT COUNT(*) FROM asset_groups WHERE id = :id");
        $existsStmt->execute([':id' => $id]);
        if ((int)$existsStmt->fetchColumn() < 1) {
            return ['success' => false, 'error' => 'Asset group not found.'];
        }

        $sql = "UPDATE asset_groups
                SET group_code    = :group_code,
                    group_name    = :group_name,
                    actual_months = :actual_months,
                    asset_code    = :asset_code
                WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':group_code'    => $data['group_code'],
                ':group_name'    => $data['group_name'],
                ':actual_months' => $data['actual_months'],
                ':asset_code'    => $data['asset_code'],
                ':id'            => $id,
            ]);

            return ['success' => true];
        } catch (\PDOException $e) {
            if ($e->getCode() === '45000') {
                return ['success' => false, 'error' => $this->extractDbErrorMessage($e)];
            }

            return ['success' => false, 'error' => $this->extractDbErrorMessage($e)];
        }
    }

    public function deleteAssetGroup(int $id): array {
        $sql = "DELETE FROM asset_groups WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);

            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'error' => 'Asset group not found.'];
            }

            return ['success' => true];
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return [
                    'success' => false,
                    'error'   => 'Cannot delete this asset group because it is used by one or more assets.',
                ];
            }

            return ['success' => false, 'error' => $this->extractDbErrorMessage($e)];
        }
    }
}