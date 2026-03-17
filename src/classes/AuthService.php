<?php
namespace App;

class AuthService {
    private \PDO $db;

    public function __construct(\PDO $db) { $this->db = $db; }

    public function isLoggedIn(): bool { return isset($_SESSION['user_id']); }

    public function login(string $username, string $password): array {
        $username = strtolower(trim($username));
        $stmt = $this->db->prepare("SELECT * FROM users WHERE LOWER(username) = LOWER(:u) LIMIT 1");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            if (($user['status'] ?? '') === 'RESTRICTED')
                return ['success' => false, 'error' => 'Account restricted. Contact admin.'];

            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['user_type'] = $user['user_type'] ?? 'USER';
            $mid = !empty($user['middle_name']) ? ' ' . substr($user['middle_name'], 0, 1) . '. ' : ' ';
            $_SESSION['full_name'] = $user['first_name'] . $mid . $user['last_name'];
            $_SESSION['must_change_password'] = !empty($user['password_changed_at']);
            $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id")
                     ->execute([':id' => $user['id']]);
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'Invalid username or password.'];
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

    public function changeUserPassword(int $userId, string $newPassword): bool {
        return $this->db->prepare(
            "UPDATE users SET password_hash = :h, password_changed_at = NULL WHERE id = :id"
        )->execute([':h' => password_hash($newPassword, PASSWORD_ARGON2ID), ':id' => $userId]);
    }

    public function resetPassword(int $userId): array {
        $ok = $this->db->prepare(
            "UPDATE users SET password_hash = :h, password_changed_at = NOW() WHERE id = :id"
        )->execute([':h' => password_hash('DefaultPass1!', PASSWORD_ARGON2ID), ':id' => $userId]);
        return $ok ? ['success' => true] : ['success' => false, 'error' => 'Reset failed.'];
    }

    public function getAllUsers(): array {
        return $this->db->query(
            "SELECT id, username, first_name, middle_name, last_name,
                    user_type, status, last_login
             FROM users ORDER BY last_name ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function registerUser(
        string $fn, string $mn, string $ln,
        string $username,
        string $type   = 'USER',
        string $status = 'ACTIVE'
    ): array {
        try {
            $this->db->prepare("
                INSERT INTO users
                    (first_name, middle_name, last_name, username,
                     password_hash, user_type, status, password_changed_at)
                VALUES (:fn, :mn, :ln, :u, :h, :t, :s, NOW())
            ")->execute([
                ':fn' => $fn,  ':mn' => $mn ?: null,
                ':ln' => $ln,  ':u'  => strtoupper(trim($username)),
                ':h'  => password_hash('DefaultPass1!', PASSWORD_ARGON2ID),
                ':t'  => $type, ':s' => $status,
            ]);
            return ['success' => true];
        } catch (\PDOException $e) {
            return [
                'success' => false,
                'error'   => $e->getCode() == 23000 ? 'Username already exists.' : $e->getMessage()
            ];
        }
    }

    public function updateUserStatusAndRole(int $userId, string $userType, string $status): bool {
        return $this->db->prepare(
            "UPDATE users SET user_type = :t, status = :s WHERE id = :id"
        )->execute([':t' => $userType, ':s' => $status, ':id' => $userId]);
    }
}
