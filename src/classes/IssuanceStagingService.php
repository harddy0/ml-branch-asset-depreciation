<?php
namespace App;

class IssuanceStagingService
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    private function normalizeDate(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') return '';
        $ts = strtotime($raw);
        if ($ts === false) return '';
        return date('Y-m-d', $ts);
    }

    public function getStagingList(array $options = []): array
    {
        $page    = max(1, (int)($options['page'] ?? 1));
        $perPage = max(1, min(100, (int)($options['per_page'] ?? 50)));

        $search          = trim((string)($options['search'] ?? ''));
        $transferStatus  = trim((string)($options['transfer_status'] ?? ''));
        $sourceStatus    = trim((string)($options['source_status'] ?? ''));
        $zone            = trim((string)($options['zone'] ?? ''));
        $region          = trim((string)($options['region'] ?? ''));
        $branchName      = trim((string)($options['branch_name'] ?? ''));
        $productCategory = trim((string)($options['product_category'] ?? ''));
        $dateFrom        = $this->normalizeDate((string)($options['date_from'] ?? ''));
        $dateTo          = $this->normalizeDate((string)($options['date_to'] ?? ''));

        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(issuance_number LIKE :search OR item_description LIKE :search OR cost_center_raw LIKE :search OR branch_name LIKE :search OR product_category LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        if ($transferStatus !== '') {
            $where[] = 'transfer_status = :transfer_status';
            $params[':transfer_status'] = $transferStatus;
        }

        if ($sourceStatus !== '') {
            $where[] = 'source_status = :source_status';
            $params[':source_status'] = $sourceStatus;
        }

        if ($zone !== '') {
            $where[] = 'zone = :zone';
            $params[':zone'] = $zone;
        }

        if ($region !== '') {
            $where[] = 'region = :region';
            $params[':region'] = $region;
        }

        if ($branchName !== '') {
            $where[] = 'branch_name = :branch_name';
            $params[':branch_name'] = $branchName;
        }

        if ($productCategory !== '') {
            $where[] = 'product_category = :product_category';
            $params[':product_category'] = $productCategory;
        }

        if ($dateFrom !== '') {
            $where[] = 'date_issued >= :date_from';
            $params[':date_from'] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo !== '') {
            $where[] = 'date_issued <= :date_to';
            $params[':date_to'] = $dateTo . ' 23:59:59';
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countStmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM issuance_staging {$whereSql}");
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = (int)($countStmt->fetchColumn() ?: 0);

        $totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 0;
        if ($totalPages > 0) {
            $page = min($page, $totalPages);
        }
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT
                id, date_issued, issuance_number, item_code, item_description,
                quantity, uom, cost_center_raw, unit_cost, total_amount,
                description_remarks, product_category, zone, region, branch_name,
                source_status, transfer_status, transferred_asset_id,
                rejection_reason, transferred_at, created_at, updated_at
            FROM issuance_staging
            {$whereSql}
            ORDER BY date_issued DESC, id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'data' => $rows,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
        ];
    }
}
