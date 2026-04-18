<?php
namespace App;

class AssetClassificationService {
    private \PDO $db;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }

    private function extractDbErrorMessage(\PDOException $e): string {
        return $e->errorInfo[2] ?? $e->getMessage();
    }

    public function createAmortizationRule(array $data): array {
        $sql = "INSERT INTO amortization_depreciation (depreciation_code, description, months, gl_code) 
                VALUES (:code, :desc, :months, :gl_code)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':code'    => $data['depreciation_code'],
                ':desc'    => $data['description'],
                ':months'  => $data['months'],
                ':gl_code' => $data['gl_code']
            ]);
            return ['success' => true];
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return ['success' => false, 'error' => 'Depreciation code already exists. Please use a unique code.'];
            }
            return ['success' => false, 'error' => $this->extractDbErrorMessage($e)];
        }
    }

    public function getAllAmortizationRules(): array {
        $sql = "SELECT ad.depreciation_code, ad.description, ad.months, ad.gl_code, gl.description AS gl_description 
                FROM amortization_depreciation ad
                LEFT JOIN gl_codes gl ON ad.gl_code = gl.gl_code
                ORDER BY ad.depreciation_code ASC";
        return $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function updateAmortizationRule(string $originalCode, array $data): array {
        $existsStmt = $this->db->prepare("SELECT COUNT(*) FROM amortization_depreciation WHERE depreciation_code = :code");
        $existsStmt->execute([':code' => $originalCode]);
        if ((int)$existsStmt->fetchColumn() < 1) {
            return ['success' => false, 'error' => 'Asset category not found.'];
        }

        $sql = "UPDATE amortization_depreciation
                SET depreciation_code = :new_code,
                    description       = :description,
                    months            = :months,
                    gl_code           = :gl_code
                WHERE depreciation_code = :original_code";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':new_code'      => $data['depreciation_code'],
                ':description'   => $data['description'],
                ':months'        => $data['months'],
                ':gl_code'       => $data['gl_code'],
                ':original_code' => $originalCode,
            ]);
            return ['success' => true];
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return ['success' => false, 'error' => 'The new depreciation code is already in use.'];
            }
            return ['success' => false, 'error' => $this->extractDbErrorMessage($e)];
        }
    }

    public function deleteAmortizationRule(string $depreciationCode): array {
        $sql = "DELETE FROM amortization_depreciation WHERE depreciation_code = :depreciation_code";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':depreciation_code' => $depreciationCode]);

            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'error' => 'Asset category not found.'];
            }
            return ['success' => true];
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return ['success' => false, 'error' => 'Cannot delete this category because it is currently assigned to one or more active assets.'];
            }
            return ['success' => false, 'error' => $this->extractDbErrorMessage($e)];
        }
    }

    /**
     * Returns options for the add-asset group selector.
     * Compatibility shape: group_code/group_name.
     */
    public function getDropdownOptions(): array {
        $sql = "
            SELECT
                ad.depreciation_code AS group_code,
                CONCAT(ad.depreciation_code, ' - ', ad.description) AS group_name
            FROM amortization_depreciation ad
            ORDER BY ad.depreciation_code ASC
        ";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Compatibility adapter for legacy group-details API.
     * Treats group_code as depreciation_code in the migrated model.
     */
    public function getGroupDetailsForAssetCreation(string $groupCode): ?array {
        $groupCode = trim($groupCode);
        if ($groupCode === '') {
            return null;
        }

        $sql = "
            SELECT
                ad.depreciation_code AS group_code,
                ad.description AS group_name,
                ad.months AS actual_months,
                ad.depreciation_code,
                ad.description AS depreciation_description
            FROM amortization_depreciation ad
            WHERE ad.depreciation_code = :group_code
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':group_code' => $groupCode]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $assetSql = "
            SELECT gl_code AS asset_code, description AS asset_name
            FROM gl_codes
            WHERE account_type = 'CREDIT'
            ORDER BY gl_code ASC
            LIMIT 1
        ";
        $assetStmt = $this->db->query($assetSql);
        $assetRow = $assetStmt ? $assetStmt->fetch(\PDO::FETCH_ASSOC) : null;

        return [
            'group_code' => $row['group_code'],
            'group_name' => $row['group_name'],
            'actual_months' => (int)$row['actual_months'],
            'asset_code' => $assetRow['asset_code'] ?? '',
            'asset_name' => $assetRow['asset_name'] ?? '',
            'depreciation_code' => $row['depreciation_code'],
            'depreciation_description' => $row['depreciation_description'],
        ];
    }
}