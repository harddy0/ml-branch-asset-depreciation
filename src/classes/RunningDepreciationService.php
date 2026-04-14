<?php
namespace App;

class RunningDepreciationService {
    private \PDO $db;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }

    public function initializeForAsset(int $assetId, float $acquisitionCost, string $groupCode): void {
        $actualMonths = $this->getActualMonthsByGroupCode($groupCode);

        if ($actualMonths <= 0) {
            throw new \RuntimeException('Unable to initialize running depreciation: invalid actual_months for selected group.');
        }

        $stmt = $this->db->prepare(
            'INSERT INTO running_depreciation (
                asset_id,
                periods_elapsed,
                periods_remaining,
                accumulated_depreciation,
                book_value,
                last_depreciation_date,
                is_fully_depreciated,
                fully_depreciated_at
            ) VALUES (
                :asset_id,
                0,
                :periods_remaining,
                0.00,
                :book_value,
                NULL,
                0,
                NULL
            )'
        );

        $stmt->execute([
            ':asset_id' => $assetId,
            ':periods_remaining' => $actualMonths,
            ':book_value' => round($acquisitionCost, 2),
        ]);
    }

    private function getActualMonthsByGroupCode(string $groupCode): int {
        if (trim($groupCode) === '') {
            return 0;
        }

        $stmt = $this->db->prepare('SELECT actual_months FROM asset_groups WHERE group_code = :group_code LIMIT 1');
        $stmt->execute([':group_code' => $groupCode]);

        return (int)($stmt->fetchColumn() ?: 0);
    }
}
