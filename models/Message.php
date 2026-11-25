<?php
class Message {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getById(int $message_id) {
        $sql = "SELECT * FROM messages WHERE message_id = :message_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["message_id" => $message_id]);
        return $stmt->fetch();
    }

    public function getAllForConversation(int $conversation_id) {
        $sql = "SELECT * FROM messages WHERE conversation_id = :conversation_id ORDER BY message_id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["conversation_id" => $conversation_id]);
        return $stmt->fetchAll();
    }

    // Used by chat/poll_messages.php
    public function getNewMessages(int $conversation_id, int $after_id) {
        $sql = "SELECT * FROM messages
                WHERE conversation_id = :conversation_id AND message_id > :after_id
                ORDER BY message_id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["conversation_id" => $conversation_id, "after_id" => $after_id]);
        return $stmt->fetchAll();
    }

    public function create(int $conversation_id, int $sender_user_id, string $message_type, string $body, ?int $order_id = null, string $image_url = ''): int {
        $sql = "INSERT INTO messages (conversation_id, sender_user_id, message_type, body, order_id, image_url)
                VALUES (:conversation_id, :sender_user_id, :message_type, :body, :order_id, :image_url)
                RETURNING message_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "conversation_id" => $conversation_id,
            "sender_user_id"  => $sender_user_id,
            "message_type"    => $message_type,
            "body"            => $body,
            "order_id"        => $order_id,
            "image_url"       => $image_url,
        ]);
        return (int) $stmt->fetchColumn();
    }
}
