<?php
namespace App;

class DashboardService
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Cost vs Book Value grouped by Main Zone.
     */
    public function getZoneFinancials(): array
    {
        $stmt = $this->db->query("
            SELECT
                a.main_zone_code          AS label,
                SUM(a.acquisition_cost)   AS total_cost,
                COALESCE(SUM(rd.book_value), 0) AS total_book_value
            FROM assets a
            LEFT JOIN running_depreciation rd ON rd.asset_id = a.id
            WHERE a.status = 'ACTIVE'
              AND a.main_zone_code IS NOT NULL
              AND a.main_zone_code <> ''
            GROUP BY a.main_zone_code
            ORDER BY total_cost DESC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Asset cost distribution grouped by expense type (category).
     */
    public function getCategoryDistribution(): array
    {
        $stmt = $this->db->query("
            SELECT
                et.expense_name           AS label,
                SUM(a.acquisition_cost)   AS value
            FROM assets a
            JOIN asset_groups ag ON ag.id = a.asset_group_id
            JOIN expense_types et ON et.id = ag.expense_type_id
            WHERE a.status = 'ACTIVE'
            GROUP BY et.id, et.expense_name
            ORDER BY value DESC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Top N branches by total acquisition cost.
     */
    public function getTopBranches(int $limit = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT
                branch_name               AS label,
                SUM(acquisition_cost)     AS value
            FROM assets
            WHERE status = 'ACTIVE'
              AND branch_name IS NOT NULL
              AND branch_name <> ''
            GROUP BY branch_name
            ORDER BY value DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}