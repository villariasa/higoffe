<?php
require_once __DIR__ . '/../config/IdGenerator.php';

class Conversation {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getById(int $conversation_id) {
        $sql = "SELECT * FROM conversations WHERE conversation_id = :conversation_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["conversation_id" => $conversation_id]);
        return $stmt->fetch();
    }

    // Customer starts (or re-opens) an inquiry with a café — re-uses any
    // conversation that isn't resolved yet instead of spawning duplicates.
    public function startOrReuse(int $customer_user_id, int $cafe_id): int {
        $existing = $this->pdo->prepare(
            "SELECT conversation_id FROM conversations
             WHERE customer_user_id = :customer_user_id AND cafe_id = :cafe_id AND status != 'resolved'
             ORDER BY created_at DESC LIMIT 1"
        );
        $existing->execute(["customer_user_id" => $customer_user_id, "cafe_id" => $cafe_id]);
        $row = $existing->fetch();
        if ($row) {
            return (int) $row['conversation_id'];
        }

        $conversation_id = IdGenerator::next($this->pdo, 'conversations', 'conversation_id');
        $sql = "INSERT INTO conversations (conversation_id, customer_user_id, cafe_id) VALUES (:conversation_id, :customer_user_id, :cafe_id)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["conversation_id" => $conversation_id, "customer_user_id" => $customer_user_id, "cafe_id" => $cafe_id]);
        return $conversation_id;
    }

    // Waiting queue, oldest first — this IS the "queue organized by
    // waiting time" requirement, no special infra needed.
    public function getWaitingQueue(int $cafe_id) {
        $sql = "SELECT cv.*, u.full_name AS customer_name
                FROM conversations cv
                JOIN users u ON cv.customer_user_id = u.user_id
                WHERE cv.cafe_id = :cafe_id AND cv.status = 'waiting'
                ORDER BY cv.created_at ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["cafe_id" => $cafe_id]);
        return $stmt->fetchAll();
    }

    public function getActiveForSeller(int $seller_user_id) {
        $sql = "SELECT cv.*, u.full_name AS customer_name, u.last_active_at AS other_last_active_at
                FROM conversations cv
                JOIN users u ON cv.customer_user_id = u.user_id
                WHERE cv.assigned_seller_user_id = :seller_user_id AND cv.status = 'active'
                ORDER BY cv.last_message_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["seller_user_id" => $seller_user_id]);
        return $stmt->fetchAll();
    }

    public function getResolvedForCafe(int $cafe_id) {
        $sql = "SELECT cv.*, u.full_name AS customer_name
                FROM conversations cv
                JOIN users u ON cv.customer_user_id = u.user_id
                WHERE cv.cafe_id = :cafe_id AND cv.status = 'resolved'
                ORDER BY cv.last_message_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["cafe_id" => $cafe_id]);
        return $stmt->fetchAll();
    }

    public function getForCustomer(int $customer_user_id) {
        $sql = "SELECT cv.*, c.cafe_name, c.logo_url, seller.last_active_at AS other_last_active_at
                FROM conversations cv
                JOIN cafes c ON cv.cafe_id = c.cafe_id
                LEFT JOIN users seller ON cv.assigned_seller_user_id = seller.user_id
                WHERE cv.customer_user_id = :customer_user_id
                ORDER BY cv.last_message_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["customer_user_id" => $customer_user_id]);
        return $stmt->fetchAll();
    }

    // Atomic claim — exactly one seller can win this even if two click
    // "Accept" at the same instant. See plan §4 / §8 scenario 1.
    public function claim(int $conversation_id, int $seller_user_id): bool {
        $sql = "UPDATE conversations
                SET assigned_seller_user_id = :seller_user_id, status = 'active'
                WHERE conversation_id = :conversation_id AND assigned_seller_user_id IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["seller_user_id" => $seller_user_id, "conversation_id" => $conversation_id]);
        return $stmt->rowCount() === 1;
    }

    public function resolve(int $conversation_id): bool {
        $sql = "UPDATE conversations SET status = 'resolved' WHERE conversation_id = :conversation_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["conversation_id" => $conversation_id]);
    }

    public function touchLastMessageAt(int $conversation_id): void {
        $sql = "UPDATE conversations SET last_message_at = NOW() WHERE conversation_id = :conversation_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["conversation_id" => $conversation_id]);
    }

    // Used by queue/poll_queue.php as the "new inquiry" badge signal.
    public function countWaiting(int $cafe_id): int {
        $sql = "SELECT COUNT(*) FROM conversations WHERE cafe_id = :cafe_id AND status = 'waiting'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["cafe_id" => $cafe_id]);
        return (int) $stmt->fetchColumn();
    }

    // Typing + read-receipt state, piggybacked on the existing poll cycle
    // instead of a WebSocket server — $viewer_role is always 'customer' or
    // 'seller' from $_SESSION['role'], never raw user input, so it's safe
    // to use for picking which column to touch.
    public function setTyping(int $conversation_id, string $viewer_role): void {
        $col = $viewer_role === 'seller' ? 'seller_typing_at' : 'customer_typing_at';
        $sql = "UPDATE conversations SET {$col} = NOW() WHERE conversation_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["id" => $conversation_id]);
    }

    public function clearTyping(int $conversation_id, string $viewer_role): void {
        $col = $viewer_role === 'seller' ? 'seller_typing_at' : 'customer_typing_at';
        $sql = "UPDATE conversations SET {$col} = NULL WHERE conversation_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["id" => $conversation_id]);
    }

    public function markRead(int $conversation_id, string $viewer_role, int $message_id): void {
        if ($message_id <= 0) return;
        $col = $viewer_role === 'seller' ? 'seller_last_read_id' : 'customer_last_read_id';
        $sql = "UPDATE conversations SET {$col} = GREATEST({$col}, :message_id) WHERE conversation_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["message_id" => $message_id, "id" => $conversation_id]);
    }

    // Returns the OTHER participant's live status for the viewer's poll —
    // "is the other person typing right now" and "what message id have
    // they read up to".
    public function getOtherStatus(int $conversation_id, string $viewer_role): array {
        $typing_col = $viewer_role === 'seller' ? 'customer_typing_at' : 'seller_typing_at';
        $read_col = $viewer_role === 'seller' ? 'customer_last_read_id' : 'seller_last_read_id';
        $sql = "SELECT {$typing_col} AS typing_at, {$read_col} AS read_id
                FROM conversations WHERE conversation_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["id" => $conversation_id]);
        $row = $stmt->fetch();
        if (!$row) return ["other_typing" => false, "other_read_id" => 0];
        $typing = $row['typing_at'] !== null && strtotime($row['typing_at']) > time() - 4;
        return ["other_typing" => $typing, "other_read_id" => (int) $row['read_id']];
    }
}
