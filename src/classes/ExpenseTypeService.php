<?php

class ExpenseTypeService {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * CREATE: Add a new expense type
     */
    public function createExpenseType($expenseName, $categoryType, $policyMonths) {
        $sql = "INSERT INTO expense_types (expense_name, category_type, policy_months) 
                VALUES (:expense_name, :category_type, :policy_months)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':expense_name' => $expenseName,
            ':category_type' => $categoryType,
            ':policy_months' => $policyMonths
        ]);
    }

    /**
     * READ: Get a single expense type by ID (for editing)
     */
    public function getExpenseTypeById($id) {
        $sql = "SELECT id, expense_name, category_type, policy_months 
                FROM expense_types 
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * READ: Get all expense types with Search and Pagination
     */
    public function getExpenseTypes($searchTerm = '', $limit = 10, $offset = 0) {
        $sql = "SELECT id, expense_name, category_type, policy_months 
                FROM expense_types";
        
        $params = [];

        if (!empty($searchTerm)) {
            $sql .= " WHERE expense_name LIKE :search 
                      OR category_type LIKE :search";
            $params[':search'] = '%' . $searchTerm . '%';
        }

        $sql .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }

        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * HELPERS: Get total count for Pagination logic
     */
    public function getTotalCount($searchTerm = '') {
        $sql = "SELECT COUNT(id) as total FROM expense_types";
        $params = [];

        if (!empty($searchTerm)) {
            $sql .= " WHERE expense_name LIKE :search 
                      OR category_type LIKE :search";
            $params[':search'] = '%' . $searchTerm . '%';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? (int)$row['total'] : 0;
    }

    /**
     * UPDATE: Modify an existing expense type
     */
    public function updateExpenseType($id, $expenseName, $categoryType, $policyMonths) {
        $sql = "UPDATE expense_types 
                SET expense_name = :expense_name, 
                    category_type = :category_type, 
                    policy_months = :policy_months 
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':expense_name' => $expenseName,
            ':category_type' => $categoryType,
            ':policy_months' => $policyMonths,
            ':id' => $id
        ]);
    }

    /**
     * DELETE: Remove an expense type
     */
    public function deleteExpenseType($id) {
        $sql = "DELETE FROM expense_types WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}