<?php
namespace App;

class CategoryService {
    private \PDO $db;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }

    /**
     * Store a new category in the database
     */
    public function createCategory(string $code, string $name, int $life): array {
        try {
            $stmt = $this->db->prepare("INSERT INTO asset_categories (category_code, category_name, asset_life_months) VALUES (?, ?, ?)");
            $stmt->execute([$code, $name, $life]);
            return ['success' => true];
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') { // Integrity constraint violation (Duplicate)
                return ['success' => false, 'error' => "Category code \"{$code}\" already exists. Please choose a different code."];
            }
            return ['success' => false, 'error' => 'Failed to add category. Please try again.'];
        }
    }

    /**
     * Fetch all category names for dropdowns
     */
    public function getAllCategoryNames(): array {
        return $this->db->query("SELECT category_name FROM asset_categories ORDER BY category_name ASC")
                        ->fetchAll(\PDO::FETCH_COLUMN);
    }
}