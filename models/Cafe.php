<?php
class Cafe {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getById(int $cafe_id) {
        $sql = "SELECT * FROM cafes WHERE cafe_id = :cafe_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["cafe_id" => $cafe_id]);
        return $stmt->fetch();
    }

    public function getByOwner(int $owner_user_id) {
        $sql = "SELECT * FROM cafes WHERE owner_user_id = :owner_user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["owner_user_id" => $owner_user_id]);
        return $stmt->fetch();
    }

    public function create(int $owner_user_id, string $cafe_name, string $description, string $address) {
        $sql = "INSERT INTO cafes (owner_user_id, cafe_name, description, address)
                VALUES (:owner_user_id, :cafe_name, :description, :address)
                RETURNING cafe_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "owner_user_id" => $owner_user_id,
            "cafe_name"     => $cafe_name,
            "description"   => $description,
            "address"       => $address,
        ]);
        return $stmt->fetchColumn();
    }

    public function update(int $cafe_id, string $cafe_name, string $description, string $address, string $logo_url, string $cover_url) {
        $sql = "UPDATE cafes
                SET cafe_name = :cafe_name, description = :description, address = :address,
                    logo_url = :logo_url, cover_url = :cover_url
                WHERE cafe_id = :cafe_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            "cafe_name"   => $cafe_name,
            "description" => $description,
            "address"     => $address,
            "logo_url"    => $logo_url,
            "cover_url"   => $cover_url,
            "cafe_id"     => $cafe_id,
        ]);
    }

    public function search(string $keyword) {
        $sql = "SELECT * FROM cafes WHERE cafe_name ILIKE :keyword ORDER BY cafe_name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["keyword" => "%{$keyword}%"]);
        return $stmt->fetchAll();
    }

    public function getAll() {
        $sql = "SELECT * FROM cafes ORDER BY cafe_id";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function setVerified(int $cafe_id, bool $is_verified) {
        $sql = "UPDATE cafes SET is_verified = :is_verified WHERE cafe_id = :cafe_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["is_verified" => $is_verified, "cafe_id" => $cafe_id]);
    }
}
