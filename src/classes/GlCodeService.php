<?php
namespace App;

class GlCodeService {
    private \PDO $db;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }

    /**
     * Store a new GL Code in the database
     */
    public function createGlCode(string $glCode, string $description, string $accountType): array {
        try {
            // Ensure type matches ENUM values strictly
            $type = strtoupper($accountType);
            if (!in_array($type, ['DEBIT', 'CREDIT'])) {
                return ['success' => false, 'error' => 'Invalid account type. Must be DEBIT or CREDIT.'];
            }

            $stmt = $this->db->prepare("INSERT INTO gl_codes (gl_code, description, account_type) VALUES (?, ?, ?)");
            $stmt->execute([$glCode, $description, $type]);
            return ['success' => true];
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') { // Integrity constraint violation (Duplicate PK)
                return ['success' => false, 'error' => "GL code \"{$glCode}\" already exists."];
            }
            return ['success' => false, 'error' => 'Failed to add GL code. Please try again.'];
        }
    }

    /**
     * Fetch all GL Codes for tables or dropdowns
     */
    public function getAllGlCodes(): array {
        return $this->db->query("SELECT gl_code, description, account_type FROM gl_codes ORDER BY gl_code ASC")
                        ->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetch paginated GL Codes for tables or dropdowns
     * @param int $limit
     * @param int $offset
     * @param string $search
     * @return array [ 'data' => [...], 'total' => int ]
     */
    public function getPaginatedGlCodes(int $limit = 20, int $offset = 0, string $search = '', string $accountType = ''): array {
        $where = '';
        $params = [];
        $clauses = [];
        if (!empty($search)) {
            $clauses[] = "(gl_code LIKE :search OR description LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        if (!empty($accountType)) {
            $clauses[] = "account_type = :type";
            $params[':type'] = strtoupper($accountType);
        }
        if (!empty($clauses)) {
            $where = 'WHERE ' . implode(' AND ', $clauses);
        }

        $stmt = $this->db->prepare("SELECT gl_code, description, account_type FROM gl_codes {$where} ORDER BY gl_code ASC LIMIT :limit OFFSET :offset");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $totalStmt = $this->db->prepare("SELECT COUNT(*) FROM gl_codes {$where}");
        foreach ($params as $key => $value) {
            $totalStmt->bindValue($key, $value);
        }
        $totalStmt->execute();
        $total = (int)$totalStmt->fetchColumn();

        return [ 'data' => $data, 'total' => $total ];
    }

    /**
     * Fetch a specific GL Code by its code
     */
    public function getGlCode(string $glCode): ?array {
        $stmt = $this->db->prepare("SELECT gl_code, description, account_type FROM gl_codes WHERE gl_code = ?");
        $stmt->execute([$glCode]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Update an existing GL Code's description or account type
     */
    public function updateGlCode(string $glCode, string $description, string $accountType): array {
        try {
            $type = strtoupper($accountType);
            if (!in_array($type, ['DEBIT', 'CREDIT'])) {
                return ['success' => false, 'error' => 'Invalid account type. Must be DEBIT or CREDIT.'];
            }

            $stmt = $this->db->prepare("UPDATE gl_codes SET description = ?, account_type = ? WHERE gl_code = ?");
            $stmt->execute([$description, $type, $glCode]);
            
            if ($stmt->rowCount() === 0) {
                // Determine if code doesn't exist, or if data was simply the same
                $check = $this->getGlCode($glCode);
                if (!$check) {
                    return ['success' => false, 'error' => 'GL code not found.'];
                }
            }
            
            return ['success' => true];
        } catch (\PDOException $e) {
            return ['success' => false, 'error' => 'Failed to update GL code. Please try again.'];
        }
    }

    /**
     * Delete a GL Code
     */
    public function deleteGlCode(string $glCode): array {
        try {
            $stmt = $this->db->prepare("DELETE FROM gl_codes WHERE gl_code = ?");
            $stmt->execute([$glCode]);
            
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'error' => 'GL code not found.'];
            }

            return ['success' => true];
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') { // Constraint violation if linked via Foreign Keys later
                return ['success' => false, 'error' => "Cannot delete GL code \"{$glCode}\" because it is in use."];
            }
            return ['success' => false, 'error' => 'Failed to delete GL code. Please try again.'];
        }
    }

    /**
     * Fetches all GL Codes for dropdown population.
     */
    public function getAllForDropdown() {
        $stmt = $this->db->query("
            SELECT gl_code, description, account_type 
            FROM gl_codes 
            ORDER BY gl_code ASC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

}