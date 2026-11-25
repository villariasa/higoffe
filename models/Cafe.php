<?php
class Cafe {
    private PDO $pdo;

    // Review aggregates, appended to every café SELECT so any card or
    // page can show "★ 4.5 (12)" without extra queries. Expects the
    // cafes table to be aliased as `c`.
    private const RATING_COLS = ", (SELECT ROUND(AVG(r.rating)::numeric, 1) FROM cafe_reviews r WHERE r.cafe_id = c.cafe_id) AS rating_avg
                                 , (SELECT COUNT(*) FROM cafe_reviews r2 WHERE r2.cafe_id = c.cafe_id) AS rating_count";

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getById(int $cafe_id) {
        $sql = "SELECT c.*" . self::RATING_COLS . " FROM cafes c WHERE c.cafe_id = :cafe_id";
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

    public function create(int $owner_user_id, string $cafe_name, string $description, string $address, ?float $latitude = null, ?float $longitude = null) {
        $sql = "INSERT INTO cafes (owner_user_id, cafe_name, description, address, latitude, longitude)
                VALUES (:owner_user_id, :cafe_name, :description, :address, :latitude, :longitude)
                RETURNING cafe_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "owner_user_id" => $owner_user_id,
            "cafe_name"     => $cafe_name,
            "description"   => $description,
            "address"       => $address,
            "latitude"      => $latitude,
            "longitude"     => $longitude,
        ]);
        return $stmt->fetchColumn();
    }

    public function update(int $cafe_id, string $cafe_name, string $description, string $address, string $logo_url, string $cover_url, ?float $latitude = null, ?float $longitude = null) {
        $sql = "UPDATE cafes
                SET cafe_name = :cafe_name, description = :description, address = :address,
                    logo_url = :logo_url, cover_url = :cover_url,
                    latitude = :latitude, longitude = :longitude
                WHERE cafe_id = :cafe_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            "cafe_name"   => $cafe_name,
            "description" => $description,
            "address"     => $address,
            "logo_url"    => $logo_url,
            "cover_url"   => $cover_url,
            "latitude"    => $latitude,
            "longitude"   => $longitude,
            "cafe_id"     => $cafe_id,
        ]);
    }

    public function getNearby(float $lat, float $lng, int $limit = 10): array {
        $sql = "SELECT c.*" . self::RATING_COLS . ", (6371 * acos(
                    LEAST(1.0, GREATEST(-1.0,
                        cos(radians(:lat)) * cos(radians(c.latitude)) *
                        cos(radians(c.longitude) - radians(:lng)) +
                        sin(radians(:lat)) * sin(radians(c.latitude))
                    ))
                )) AS distance_km
                FROM cafes c
                WHERE c.latitude IS NOT NULL AND c.longitude IS NOT NULL
                ORDER BY distance_km ASC
                LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':lat', $lat);
        $stmt->bindValue(':lng', $lng);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function search(string $keyword, ?float $lat = null, ?float $lng = null): array {
        if ($lat !== null && $lng !== null) {
            $sql = "SELECT c.*" . self::RATING_COLS . ", (6371 * acos(
                        LEAST(1.0, GREATEST(-1.0,
                            cos(radians(:lat)) * cos(radians(c.latitude)) *
                            cos(radians(c.longitude) - radians(:lng)) +
                            sin(radians(:lat)) * sin(radians(c.latitude))
                        ))
                    )) AS distance_km
                    FROM cafes c
                    WHERE c.cafe_name ILIKE :keyword
                    ORDER BY (CASE WHEN c.latitude IS NOT NULL THEN 0 ELSE 1 END), distance_km ASC, c.cafe_name";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(["keyword" => "%{$keyword}%", "lat" => $lat, "lng" => $lng]);
            return $stmt->fetchAll();
        }

        $sql = "SELECT c.*" . self::RATING_COLS . " FROM cafes c WHERE c.cafe_name ILIKE :keyword ORDER BY c.cafe_name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["keyword" => "%{$keyword}%"]);
        return $stmt->fetchAll();
    }

    public function getAll(?float $lat = null, ?float $lng = null): array {
        if ($lat !== null && $lng !== null) {
            $sql = "SELECT c.*" . self::RATING_COLS . ", (6371 * acos(
                        LEAST(1.0, GREATEST(-1.0,
                            cos(radians(:lat)) * cos(radians(c.latitude)) *
                            cos(radians(c.longitude) - radians(:lng)) +
                            sin(radians(:lat)) * sin(radians(c.latitude))
                        ))
                    )) AS distance_km
                    FROM cafes c
                    ORDER BY (CASE WHEN c.latitude IS NOT NULL THEN 0 ELSE 1 END), distance_km ASC, c.cafe_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(["lat" => $lat, "lng" => $lng]);
            return $stmt->fetchAll();
        }

        $sql = "SELECT c.*" . self::RATING_COLS . " FROM cafes c ORDER BY c.cafe_id";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function setVerified(int $cafe_id, bool $is_verified) {
        $sql = "UPDATE cafes SET is_verified = :is_verified WHERE cafe_id = :cafe_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["is_verified" => $is_verified, "cafe_id" => $cafe_id]);
    }
}
