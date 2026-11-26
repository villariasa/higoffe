<?php
class User {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getById(int $user_id) {
        $sql = "SELECT * FROM users WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["user_id" => $user_id]);
        return $stmt->fetch();
    }

    public function getByEmail(string $email) {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["email" => $email]);
        return $stmt->fetch();
    }

    public function create(string $role, string $full_name, string $email, string $password, string $phone = '') {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (role, full_name, email, password_hash, phone)
                VALUES (:role, :full_name, :email, :password_hash, :phone)
                RETURNING user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "role"          => $role,
            "full_name"     => $full_name,
            "email"         => $email,
            "password_hash" => $password_hash,
            "phone"         => $phone,
        ]);
        return $stmt->fetchColumn();
    }

    public function updateProfile(int $user_id, string $bio, string $avatar_url, string $full_name): void {
        $sql = "UPDATE users SET bio = :bio, avatar_url = :avatar_url, full_name = :full_name WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["bio" => $bio, "avatar_url" => $avatar_url, "full_name" => $full_name, "user_id" => $user_id]);
    }

    public function setVerificationToken(int $user_id, string $token, string $expires_at): void {
        $sql = "UPDATE users SET verification_token = :token, verification_token_expires_at = :expires_at
                WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["token" => $token, "expires_at" => $expires_at, "user_id" => $user_id]);
    }

    public function getByVerificationToken(string $token) {
        $sql = "SELECT * FROM users WHERE verification_token = :token";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["token" => $token]);
        return $stmt->fetch();
    }

    public function markVerified(int $user_id): void {
        $sql = "UPDATE users
                SET email_verified = TRUE, verification_token = NULL, verification_token_expires_at = NULL
                WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["user_id" => $user_id]);
    }

    public function setSuspended(int $user_id, bool $is_suspended) {
        $sql = "UPDATE users SET is_suspended = :is_suspended WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        // pdo_pgsql turns PHP false into '' which Postgres rejects for
        // boolean columns — bind as 'true'/'false' strings instead.
        return $stmt->execute(["is_suspended" => $is_suspended ? 'true' : 'false', "user_id" => $user_id]);
    }

    // Hard-delete a user. The DB schema uses ON DELETE CASCADE / SET NULL on
    // child tables (conversations, orders, posts, notifications, etc.) so the
    // row can be removed cleanly. Admin accounts are protected — an admin
    // cannot delete themselves or another admin this way.
    public function delete(int $user_id, int $requesting_admin_id): bool {
        if ($user_id === $requesting_admin_id) {
            return false; // can't delete yourself
        }
        // Guard: never delete another admin.
        $check = $this->pdo->prepare("SELECT role FROM users WHERE user_id = :id");
        $check->execute(["id" => $user_id]);
        $target = $check->fetch();
        if (!$target || $target['role'] === 'admin') {
            return false;
        }
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE user_id = :user_id");
        $stmt->execute(["user_id" => $user_id]);
        return $stmt->rowCount() === 1;
    }

    // Admin user list
    public function getAll() {
        $sql = "SELECT * FROM users ORDER BY user_id";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
}
