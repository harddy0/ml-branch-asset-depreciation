<?php
namespace App;

class AuthService {
    private \PDO $db;

    public function __construct(\PDO $db) { $this->db = $db; }

    public function isLoggedIn(): bool { return isset($_SESSION['user_id']); }

    public function isAdmin(): bool {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'ADMIN';
    }

    // ── Authentication ────────────────────────────────────────

    public function login(string $username, string $password): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE LOWER(username) = LOWER(:u) LIMIT 1"
        );
        $stmt->execute([':u' => trim($username)]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid username or password.'];
        }

        if (($user['status'] ?? 'ACTIVE') === 'RESTRICTED') {
            return ['success' => false, 'error' => 'Your account has been restricted. Please contact your administrator.'];
        }

        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['user_type'] = $user['user_type'] ?? 'USER';
        $mid = !empty($user['middle_name'])
            ? ' ' . substr($user['middle_name'], 0, 1) . '. '
            : ' ';
        $_SESSION['full_name'] = $user['first_name'] . $mid . $user['last_name'];
        // password_changed_at IS NOT NULL → new/reset account, must change password first
        $_SESSION['must_change_password'] = !empty($user['password_changed_at']);

        $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id")
            ->execute([':id' => $user['id']]);

        return ['success' => true];
    }

    public function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // ── Password Management ───────────────────────────────────

    /** User sets their own new password (clears the must-change flag). */
    public function changeUserPassword(int $userId, string $newPassword): bool {
        return $this->db->prepare(
            "UPDATE users SET password_hash = :h, password_changed_at = NULL WHERE id = :id"
        )->execute([':h' => password_hash($newPassword, PASSWORD_ARGON2ID), ':id' => $userId]);
    }

    /**
     * Admin resets a user's password to the system default: Mlinc1234@
     * Sets password_changed_at = NOW() so the user is forced to change it on next login.
     */
    public function resetPassword(int $userId): array {
        $ok = $this->db->prepare(
            "UPDATE users SET password_hash = :h, password_changed_at = NOW() WHERE id = :id"
        )->execute([':h' => password_hash('Mlinc1234@', PASSWORD_ARGON2ID), ':id' => $userId]);
        return $ok
            ? ['success' => true]
            : ['success' => false, 'error' => 'Reset failed.'];
    }

    // ── User Queries ──────────────────────────────────────────

    public function getAllUsers(): array {
        return $this->db->query(
            "SELECT id, username, first_name, middle_name, last_name,
                    user_type, status, last_login, created_at
             FROM users
             ORDER BY last_name ASC, first_name ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getUserById(int $id): array|false {
        $stmt = $this->db->prepare(
            "SELECT id, first_name, middle_name, last_name, username, user_type, status
             FROM users WHERE id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /** Check whether a given employee ID already exists in the users table. */
    public function idExists(int $id): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    // ── User Mutations ────────────────────────────────────────

    /**
     * Create a new user.
     *  - id       : manually supplied employee ID (no AUTO_INCREMENT)
     *  - username : auto-generated as first 4 chars of last name + id (uppercase)
     *  - password : Mlinc1234@ (user forced to change on first login)
     */
    public function registerUser(
        int    $id,
        string $fn, string $mn, string $ln,
        string $type = 'USER'
    ): array {
        $username = strtoupper(substr(trim($ln), 0, 4) . $id);
        try {
            $this->db->prepare("
                INSERT INTO users
                    (id, first_name, middle_name, last_name, username,
                     password_hash, user_type, status, password_changed_at)
                VALUES (:id, :fn, :mn, :ln, :u, :h, :t, 'ACTIVE', NOW())
            ")->execute([
                ':id' => $id,
                ':fn' => trim($fn),
                ':mn' => trim($mn) ?: null,
                ':ln' => trim($ln),
                ':u'  => $username,
                ':h'  => password_hash('Mlinc1234@', PASSWORD_ARGON2ID),
                ':t'  => $type,
            ]);
            return ['success' => true, 'username' => $username];
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                // Could be duplicate id OR duplicate username
                $msg = str_contains($e->getMessage(), 'username')
                    ? 'Username already exists.'
                    : 'Employee ID already exists.';
            } else {
                $msg = $e->getMessage();
            }
            return ['success' => false, 'error' => $msg];
        }
    }

    /**
     * Update an existing user's name and role.
     * Username is always re-derived from last name + id (cannot be changed manually).
     */
    public function updateUser(
        int    $id,
        string $fn, string $mn, string $ln,
        string $type
    ): array {
        $username = strtoupper(substr(trim($ln), 0, 4) . $id);
        try {
            $this->db->prepare("
                UPDATE users
                SET first_name  = :fn,
                    middle_name = :mn,
                    last_name   = :ln,
                    username    = :u,
                    user_type   = :t
                WHERE id = :id
            ")->execute([
                ':fn' => trim($fn),
                ':mn' => trim($mn) ?: null,
                ':ln' => trim($ln),
                ':u'  => $username,
                ':t'  => $type,
                ':id' => $id,
            ]);
            return ['success' => true, 'username' => $username];
        } catch (\PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Toggle a user's status between ACTIVE and RESTRICTED.
     * An admin cannot restrict their own account.
     */
    public function setUserStatus(int $userId, string $status, int $currentUserId): array {
        if (!in_array($status, ['ACTIVE', 'RESTRICTED'])) {
            return ['success' => false, 'error' => 'Invalid status.'];
        }
        if ($userId === $currentUserId && $status === 'RESTRICTED') {
            return ['success' => false, 'error' => 'You cannot restrict your own account.'];
        }
        $ok = $this->db->prepare(
            "UPDATE users SET status = :s WHERE id = :id"
        )->execute([':s' => $status, ':id' => $userId]);
        return $ok ? ['success' => true] : ['success' => false, 'error' => 'Update failed.'];
    }
}