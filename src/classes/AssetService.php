<?php
namespace App;

class AssetService {
    private \PDO $db;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }

    /**
     * ==========================================
     * CREATE (Add New Asset Only)
     * ==========================================
     */
    public function createAsset(array $data, int $userId): array {
        try {
            $this->db->beginTransaction();

            $sql = "
                INSERT INTO assets (
                    system_asset_code, reference_no, 
                    main_zone_code, zone_code, region_code, cost_center_code, branch_name,
                    group_code, asset_code, depreciation_code, 
                    description, serial_number, quantity, property_type,
                    date_received, depreciation_start_date, depreciation_end_date,
                    depreciation_on, depreciation_day,
                    acquisition_cost, cost_unit, item_code, monthly_depreciation, status,
                    created_by
                ) VALUES (
                    :system_asset_code, :reference_no, 
                    :main_zone_code, :zone_code, :region_code, :cost_center_code, :branch_name,
                    :group_code, :asset_code, :depreciation_code, 
                    :description, :serial_number, :quantity, :property_type,
                    :date_received, :depreciation_start_date, :depreciation_end_date,
                    :depreciation_on, :depreciation_day, 
                    :acquisition_cost, :cost_unit, :item_code, :monthly_depreciation, :status,
                    :created_by
                )
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':system_asset_code'       => $data['system_asset_code'],
                ':reference_no'            => $data['reference_no'],
                ':main_zone_code'          => $data['main_zone_code'],
                ':zone_code'               => $data['zone_code'],
                ':region_code'             => $data['region_code'],
                ':cost_center_code'        => $data['cost_center_code'],
                ':branch_name'             => $data['branch_name'],
                ':group_code'              => $data['group_code'],
                ':asset_code'              => $data['asset_code'],
                ':depreciation_code'       => $data['depreciation_code'],
                ':description'             => $data['description'],
                ':serial_number'           => $data['serial_number'],
                ':quantity'                => $data['quantity'],
                ':property_type'           => $data['property_type'],
                ':date_received'           => $data['date_received'],
                ':depreciation_start_date' => $data['depreciation_start_date'],
                ':depreciation_end_date'   => $data['depreciation_end_date'],
                ':depreciation_on'         => $data['depreciation_on'],
                ':depreciation_day'        => $data['depreciation_day'],
                ':acquisition_cost'        => $data['acquisition_cost'],
                ':cost_unit'               => $data['cost_unit'],
                ':item_code'               => $data['item_code'],
                ':monthly_depreciation'    => $data['monthly_depreciation'],
                ':status'                  => $data['status'],
                ':created_by'              => $userId
            ]);

            $assetId = (int)$this->db->lastInsertId();

            $runningDepService = new RunningDepreciationService($this->db);
            $runningDepService->initializeForAsset(
                $assetId,
                (float)$data['acquisition_cost'],
                (string)$data['group_code']
            );

            $this->generateAssetLedgerEntries($assetId, $data);

            $this->db->commit();

            return ['success' => true, 'asset_id' => $assetId];

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * ==========================================
     * READ (Get Single Asset)
     * ==========================================
     */
    public function getAssetById(int $id): ?array {
        $sql = "SELECT * FROM assets WHERE id = :id LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * ==========================================
     * READ (Get Multiple / Paginated / Filtered)
     * ==========================================
     */
    public function getAssets(array $filters = [], int $limit = 50, int $offset = 0): array {
        $sql = "
            SELECT id, system_asset_code, description, branch_name, 
                   acquisition_cost, status, date_received 
            FROM assets 
            WHERE 1=1
        ";
        
        $params = [];

        // Apply dynamic filters
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['zone_code'])) {
            $sql .= " AND zone_code = :zone_code";
            $params[':zone_code'] = $filters['zone_code'];
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        
        // Bind parameters safely
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * ==========================================
     * UPDATE (Edit Asset Info)
     * ==========================================
     */
    public function updateAsset(int $id, array $data): array {
        $sql = "
            UPDATE assets 
            SET 
                reference_no = :reference_no,
                description = :description,
                serial_number = :serial_number
            WHERE id = :id
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id'            => $id,
                ':reference_no'  => $data['reference_no'] ?? null,
                ':description'   => $data['description'],
                ':serial_number' => $data['serial_number'] ?? null,
            ]);

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * ==========================================
     * DELETE / STATUS UPDATE (Dispose/Sell)
     * ==========================================
     */
    public function changeStatus(int $id, string $newStatus, ?string $retirementDate = null): array {
        $allowedStatuses = ['ACTIVE', 'SOLD', 'DISPOSED', 'DEPRECIATED'];
        
        if (!in_array($newStatus, $allowedStatuses)) {
            return ['success' => false, 'error' => 'Invalid status provided.'];
        }

        $sql = "UPDATE assets SET status = :status";
        $params = [
            ':id' => $id, 
            ':status' => $newStatus
        ];

        // If the asset is sold or disposed of, we log the exact date it left the company
        if (in_array($newStatus, ['SOLD', 'DISPOSED']) && $retirementDate) {
            $sql .= ", retirement_date = :retirement_date";
            $params[':retirement_date'] = $retirementDate;
        }

        $sql .= " WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generates the fully denormalized GL ledger entries for the entire lifespan of the asset.
     */
    private function generateAssetLedgerEntries(int $assetId, array $data): void {
        // 1. Fetch the total lifespan months from the asset_groups table
        $stmt = $this->db->prepare('SELECT actual_months FROM asset_groups WHERE group_code = :group_code LIMIT 1');
        $stmt->execute([':group_code' => $data['group_code']]);
        $lifespanMonths = (int)($stmt->fetchColumn() ?: 0);

        if ($lifespanMonths <= 0) {
            return; // Safety check
        }

        $acquisitionCost = (float)$data['acquisition_cost'];
        $monthlyDepreciation = (float)$data['monthly_depreciation'];
        $startDate = new \DateTime($data['depreciation_start_date']);

        // 2. Prepare the exact statement matching your depreciation_ledger table
        $sql = "
            INSERT INTO depreciation_ledger (
                asset_id, system_asset_code, description,
                main_zone_code, zone_code, region_code, cost_center_code, branch_name,
                group_code, asset_code, depreciation_code, property_type,
                acquisition_cost, monthly_depreciation,
                period_date, period_month, period_year,
                periods_elapsed, periods_remaining, period_depreciation_expense,
                accumulated_depreciation, book_value,
                gl_debit_code, gl_debit_amount, gl_credit_code, gl_credit_amount
            ) VALUES (
                :asset_id, :system_asset_code, :description,
                :main_zone_code, :zone_code, :region_code, :cost_center_code, :branch_name,
                :group_code, :asset_code, :depreciation_code, :property_type,
                :acquisition_cost, :monthly_depreciation,
                :period_date, :period_month, :period_year,
                :periods_elapsed, :periods_remaining, :period_depreciation_expense,
                :accumulated_depreciation, :book_value,
                :gl_debit_code, :gl_debit_amount, :gl_credit_code, :gl_credit_amount
            )
        ";
        
        $insertStmt = $this->db->prepare($sql);

        $accumulatedDepreciation = 0.00;

        // 3. Loop through every month and compute the running values
        for ($i = 0; $i < $lifespanMonths; $i++) {
            $currentPeriodDate = clone $startDate;
            $currentPeriodDate->modify("+{$i} months");
            
            $periodsElapsed = $i + 1;
            $periodsRemaining = $lifespanMonths - $periodsElapsed;
            $accumulatedDepreciation += $monthlyDepreciation;
            $bookValue = $acquisitionCost - $accumulatedDepreciation;
            
            // Adjust final month rounding to ensure book value hits exactly 0.00
            if ($periodsRemaining === 0) {
                $accumulatedDepreciation = $acquisitionCost;
                $bookValue = 0.00;
            }

            $insertStmt->execute([
                // Identity Snapshot
                ':asset_id'                   => $assetId,
                ':system_asset_code'          => $data['system_asset_code'],
                ':description'                => $data['description'],
                
                // Location Snapshot
                ':main_zone_code'             => $data['main_zone_code'],
                ':zone_code'                  => $data['zone_code'],
                ':region_code'                => $data['region_code'],
                ':cost_center_code'           => $data['cost_center_code'],
                ':branch_name'                => $data['branch_name'],
                
                // Classification Snapshot
                ':group_code'                 => $data['group_code'],
                ':asset_code'                 => $data['asset_code'],
                ':depreciation_code'          => $data['depreciation_code'],
                ':property_type'              => $data['property_type'],
                
                // Financial Snapshot
                ':acquisition_cost'           => $acquisitionCost,
                ':monthly_depreciation'       => $monthlyDepreciation,
                
                // Period Info
                ':period_date'                => $currentPeriodDate->format('Y-m-d'),
                ':period_month'               => (int)$currentPeriodDate->format('n'),
                ':period_year'                => (int)$currentPeriodDate->format('Y'),
                
                // Computed Values at This Period
                ':periods_elapsed'            => $periodsElapsed,
                ':periods_remaining'          => $periodsRemaining,
                ':period_depreciation_expense'=> $monthlyDepreciation,
                ':accumulated_depreciation'   => round($accumulatedDepreciation, 2),
                ':book_value'                 => round($bookValue, 2),
                
                // Journal Entry (GL Posting)
                ':gl_debit_code'              => $data['depreciation_code'],
                ':gl_debit_amount'            => $monthlyDepreciation,
                ':gl_credit_code'             => $data['asset_code'],
                ':gl_credit_amount'           => $monthlyDepreciation
            ]);
        }
    }

}