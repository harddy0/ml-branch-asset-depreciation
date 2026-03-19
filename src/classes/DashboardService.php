<?php
namespace App;

class DashboardService {
    private \PDO $db;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }

    // New Chart: Cost vs Book Value grouped by Zone
    public function getZoneFinancials(): array {
        $stmt = $this->db->query("
            SELECT 
                a.zone as label, 
                SUM(a.acquisition_cost) as total_cost,
                COALESCE(SUM(rd.book_value), 0) as total_book_value
            FROM assets a
            LEFT JOIN (
                SELECT asset_id, book_value
                FROM running_depreciation
                WHERE (asset_id, period_date) IN (
                    SELECT asset_id, MAX(period_date)
                    FROM running_depreciation
                    GROUP BY asset_id
                )
            ) rd ON a.id = rd.asset_id
            WHERE a.status = 'ACTIVE' AND a.zone IS NOT NULL AND a.zone != ''
            GROUP BY a.zone
            ORDER BY total_cost DESC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Original Chart 1: Categories
    public function getCategoryDistribution(): array {
        $stmt = $this->db->query("
            SELECT 
                c.category_name as label, 
                SUM(a.acquisition_cost) as value 
            FROM assets a
            JOIN asset_categories c ON a.category_code = c.category_code
            WHERE a.status = 'ACTIVE'
            GROUP BY c.category_code, c.category_name
            ORDER BY value DESC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Original Chart 2: Branches
    public function getTopBranches(int $limit = 5): array {
        $stmt = $this->db->prepare("
            SELECT 
                branch_name as label, 
                SUM(acquisition_cost) as value 
            FROM assets
            WHERE status = 'ACTIVE' AND branch_name IS NOT NULL AND branch_name != ''
            GROUP BY branch_name
            ORDER BY value DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}