<?php

namespace App;

class AssetGroupService {
    private $db;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }

    /**
     * ==========================================
     * 1. CORE VALIDATION & HELPER METHODS
     * ==========================================
     */

    /**
     * Validates that the actual months do not exceed the mother expense type's policy.
     */
    private function validateMonths($expenseTypeId, $actualMonths) {
        $stmt = $this->db->prepare("SELECT policy_months, expense_name FROM expense_types WHERE id = ?");
        $stmt->execute([$expenseTypeId]);
        $policy = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$policy) {
            throw new \Exception("Invalid Expense Type selected.");
        }

        if ($actualMonths > $policy['policy_months']) {
            throw new \Exception("Actual months ({$actualMonths}) cannot exceed the maximum policy of {$policy['policy_months']} months for {$policy['expense_name']}.");
        }

        return true;
    }

    /**
     * Fetches the correct account type (DEBIT/CREDIT) directly from the gl_codes table.
     */
    private function getGlAccountType($glCode) {
        $stmt = $this->db->prepare("SELECT account_type FROM gl_codes WHERE gl_code = ?");
        $stmt->execute([$glCode]);

        $type = $stmt->fetchColumn();

        if (!$type) {
            throw new \Exception("GL Code {$glCode} does not exist in the Chart of Accounts.");
        }

        return $type;
    }

    /**
     * Checks if the asset group is actively being used by any assets.
     */
    public function isGroupInUse($assetGroupId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM assets WHERE asset_group_id = ?");
        $stmt->execute([$assetGroupId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * ==========================================
     * 2. CRUD OPERATIONS (WRITE)
     * ==========================================
     */

    public function create(array $data) {
        try {
            // 1. Enforce Mother Lookup Table Rule
            $this->validateMonths($data['expense_type_id'], $data['actual_months']);

            // 2. Fetch reliable GL Types from the gl_codes table
            $assetGlType = $this->getGlAccountType($data['asset_gl_code']);
            $expenseGlType = $this->getGlAccountType($data['expense_gl_code']);

            // 3. Execute Insertion
            $stmt = $this->db->prepare("
                INSERT INTO asset_groups (
                    group_name, expense_type_id, actual_months, 
                    asset_gl_code, asset_gl_type, 
                    expense_gl_code, expense_gl_type
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                trim($data['group_name']),
                $data['expense_type_id'],
                $data['actual_months'],
                $data['asset_gl_code'],
                $assetGlType,
                $data['expense_gl_code'],
                $expenseGlType
            ]);

            return [
                'success' => true, 
                'message' => 'Asset group created successfully.',
                'id' => $this->db->lastInsertId()
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function update($id, array $data) {
        try {
            // 1. Enforce Mother Lookup Table Rule
            $this->validateMonths($data['expense_type_id'], $data['actual_months']);

            // 2. Fetch reliable GL Types from the gl_codes table
            $assetGlType = $this->getGlAccountType($data['asset_gl_code']);
            $expenseGlType = $this->getGlAccountType($data['expense_gl_code']);

            // 3. Execute Update
            $stmt = $this->db->prepare("
                UPDATE asset_groups 
                SET group_name = ?, 
                    expense_type_id = ?, 
                    actual_months = ?, 
                    asset_gl_code = ?, 
                    asset_gl_type = ?, 
                    expense_gl_code = ?, 
                    expense_gl_type = ?
                WHERE id = ?
            ");

            $stmt->execute([
                trim($data['group_name']),
                $data['expense_type_id'],
                $data['actual_months'],
                $data['asset_gl_code'],
                $assetGlType,
                $data['expense_gl_code'],
                $expenseGlType,
                $id
            ]);

            return ['success' => true, 'message' => 'Asset group updated successfully.'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function delete($id) {
        try {
            if ($this->isGroupInUse($id)) {
                throw new \Exception("Cannot delete: This asset group is currently assigned to active assets.");
            }

            $stmt = $this->db->prepare("DELETE FROM asset_groups WHERE id = ?");
            $stmt->execute([$id]);

            return ['success' => true, 'message' => 'Asset group deleted successfully.'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * ==========================================
     * 3. DATA RETRIEVAL METHODS (READ)
     * ==========================================
     */

    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT 
                ag.*, 
                et.expense_name, 
                et.policy_months,
                gl_a.description AS asset_gl_description,
                gl_e.description AS expense_gl_description
            FROM asset_groups ag
            LEFT JOIN expense_types et ON ag.expense_type_id = et.id
            LEFT JOIN gl_codes gl_a ON ag.asset_gl_code = gl_a.gl_code
            LEFT JOIN gl_codes gl_e ON ag.expense_gl_code = gl_e.gl_code
            WHERE ag.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getByExpenseType($expenseTypeId) {
        $stmt = $this->db->prepare("
            SELECT id, group_name, actual_months, asset_gl_code, expense_gl_code 
            FROM asset_groups 
            WHERE expense_type_id = ?
            ORDER BY group_name ASC
        ");
        $stmt->execute([$expenseTypeId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getPaginatedList($page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        $params = [];
        
        $whereClause = "";
        if (!empty($search)) {
            $whereClause = "WHERE ag.group_name LIKE ? OR et.expense_name LIKE ? OR ag.asset_gl_code LIKE ? OR ag.expense_gl_code LIKE ?";
            $searchParam = "%{$search}%";
            $params = array_fill(0, 4, $searchParam);
        }

        // Get total records for pagination
        $countQuery = "
            SELECT COUNT(*) 
            FROM asset_groups ag
            LEFT JOIN expense_types et ON ag.expense_type_id = et.id
            $whereClause
        ";
        $stmtCount = $this->db->prepare($countQuery);
        $stmtCount->execute($params);
        $totalRecords = $stmtCount->fetchColumn();

        // Get actual data
        $dataQuery = "
            SELECT 
                ag.id, 
                ag.group_name, 
                ag.actual_months, 
                ag.asset_gl_code, 
                ag.expense_gl_code,
                ag.expense_type_id,
                et.expense_name,
                et.policy_months
            FROM asset_groups ag
            LEFT JOIN expense_types et ON ag.expense_type_id = et.id
            $whereClause
            ORDER BY ag.id DESC
            LIMIT $limit OFFSET $offset
        ";
        
        $stmtData = $this->db->prepare($dataQuery);
        // Need to bind LIMIT and OFFSET explicitly as integers if using emulated prepares,
        // but since we injected them directly into the string securely via int variables, execute($params) is safe.
        $stmtData->execute($params);
        $data = $stmtData->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'data' => $data,
            'total_records' => $totalRecords,
            'total_pages' => ceil($totalRecords / $limit),
            'current_page' => $page
        ];
    }

    /**
     * Fetches all asset groups with GL account details for dropdown population.
     * Returns array formatted for frontend consumption with auto-fill data.
     * 
     * @return array Array of asset groups with GL details:
     *   [
     *     {
     *       "id": 1,
     *       "group_name": "Office Equipment",
     *       "display": "1 - Office Equipment",
     *       "asset_gl_code": "1231101",
     *       "asset_gl_type": "DEBIT",
     *       "asset_gl_description": "Equipment - Office",
     *       "expense_gl_code": "5101100",
     *       "expense_gl_type": "DEBIT",
     *       "expense_gl_description": "Depreciation Expense - Office",
     *       "actual_months": 60
     *     },
     *     ...
     *   ]
     */
    public function getGroupsForDropdown(): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                ag.id,
                ag.group_name,
                ag.expense_type_id,
                et.expense_name,
                et.category_type,
                ag.asset_gl_code,
                ag.asset_gl_type,
                ag.expense_gl_code,
                ag.expense_gl_type,
                ag.actual_months,
                gl_asset.description AS asset_gl_description,
                gl_expense.description AS expense_gl_description
            FROM asset_groups ag
            LEFT JOIN expense_types et ON et.id = ag.expense_type_id
            LEFT JOIN gl_codes gl_asset ON gl_asset.gl_code = ag.asset_gl_code
            LEFT JOIN gl_codes gl_expense ON gl_expense.gl_code = ag.expense_gl_code
            ORDER BY ag.id ASC
        ");
        
        $stmt->execute();
        $groups = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Format each group with display string for dropdown
        return array_map(function($group) {
            return [
                'id' => (int)$group['id'],
                'group_name' => $group['group_name'],
                'display' => $group['id'] . ' - ' . $group['group_name'],
                'expense_type_id' => (int)$group['expense_type_id'],
                'expense_name' => $group['expense_name'] ?? '',
                'category_type' => $group['category_type'] ?? '',
                'asset_gl_code' => $group['asset_gl_code'],
                'asset_gl_type' => $group['asset_gl_type'],
                'asset_gl_description' => $group['asset_gl_description'] ?? '',
                'expense_gl_code' => $group['expense_gl_code'],
                'expense_gl_type' => $group['expense_gl_type'],
                'expense_gl_description' => $group['expense_gl_description'] ?? '',
                'actual_months' => (int)$group['actual_months']
            ];
        }, $groups);
    }

    /**
     * Fetches the asset group dropdown options used by the depreciation list filter.
     * Uses asset_groups joined with gl_codes and formats labels as "GL_CODE - Description".
     *
     * @return array<int,array{id:int,label:string}>
     */
    public function getFilterOptions(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                ag.id,
                ag.group_name,
                ag.asset_gl_code,
                gc.description AS gl_description
            FROM asset_groups ag
            LEFT JOIN gl_codes gc ON ag.asset_gl_code = gc.gl_code
            ORDER BY ag.asset_gl_code ASC, ag.id ASC
        ");

        $stmt->execute();
        $options = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            $code = trim((string)($row['asset_gl_code'] ?? ''));
            $description = trim((string)($row['gl_description'] ?? ''));
            $groupName = trim((string)($row['group_name'] ?? ''));

            $label = $code;
            if ($description !== '') {
                $label = $code !== '' ? $code . ' - ' . $description : $description;
            }
            if ($label === '') {
                $label = $groupName !== '' ? $groupName : 'Group ' . ((int)$row['id']);
            }

            return [
                'id' => (int)$row['id'],
                'label' => $label,
            ];
        }, $options);
    }
}