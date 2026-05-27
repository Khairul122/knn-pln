<?php
require_once CORE_PATH . '/Model.php';

class User extends Model
{
    protected string $table = 'users';

    public function findByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE email = ? AND is_active = 1 LIMIT 1"
        );
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public function updateLastLogin(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET last_login = NOW() WHERE id = ?"
        );
        $stmt->execute([$id]);
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT id, name, email, role, is_active, last_login, created_at
             FROM {$this->table} WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
