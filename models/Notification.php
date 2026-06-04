<?php

require_once __DIR__ . "/../config/database.php";

class Notification {

    private $conn;
    private $table = "Notifications";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Insert a new notification row.
     * Pass null for $customerId to create a broadcast visible to all customers.
     * Returns the new NotificationID.
     */
    public function create($customerId, string $type, string $title, string $message): int {
        $query = "INSERT INTO " . $this->table . "
                  (CustomerID, Type, Title, Message, CreatedAt)
                  VALUES (?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$customerId, $type, $title, $message]);
        return (int)$this->conn->lastInsertId();
    }

    /**
     * All notifications for a customer, newest first.
     * Includes broadcasts (CustomerID IS NULL).
     */
    public function getAllByCustomerId(int $customerId): array {
        $query = "SELECT * FROM " . $this->table . "
                  WHERE CustomerID = ? OR CustomerID IS NULL
                  ORDER BY CreatedAt DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Unread notifications for a customer, newest first.
     * Includes unread broadcasts.
     */
    public function getUnreadByCustomerId(int $customerId): array {
        $query = "SELECT * FROM " . $this->table . "
                  WHERE (CustomerID = ? OR CustomerID IS NULL) AND IsRead = 0
                  ORDER BY CreatedAt DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count of unread notifications for a customer (includes broadcasts).
     */
    public function getUnreadCount(int $customerId): int {
        $query = "SELECT COUNT(*) AS cnt FROM " . $this->table . "
                  WHERE (CustomerID = ? OR CustomerID IS NULL) AND IsRead = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$customerId]);
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    }

    /**
     * Mark a single notification as read.
     * The WHERE clause enforces ownership: the caller can only mark their own
     * notifications, or broadcasts (CustomerID IS NULL).
     * Returns number of rows updated (0 = not found or already read or not owned).
     */
    public function markAsRead(int $notificationId, int $customerId): int {
        $query = "UPDATE " . $this->table . "
                  SET IsRead = 1, ReadAt = NOW()
                  WHERE NotificationID = ?
                    AND (CustomerID = ? OR CustomerID IS NULL)
                    AND IsRead = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$notificationId, $customerId]);
        return $stmt->rowCount();
    }

    /**
     * Mark all of a customer's notifications (and broadcasts) as read.
     * Returns number of rows updated.
     */
    public function markAllAsRead(int $customerId): int {
        $query = "UPDATE " . $this->table . "
                  SET IsRead = 1, ReadAt = NOW()
                  WHERE (CustomerID = ? OR CustomerID IS NULL) AND IsRead = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$customerId]);
        return $stmt->rowCount();
    }

    /**
     * Fetch a single notification by ID.
     */
    public function getById(int $notificationId): ?array {
        $query = "SELECT * FROM " . $this->table . " WHERE NotificationID = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$notificationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Delete a customer-owned notification.
     * Broadcasts (CustomerID IS NULL) cannot be deleted via this method.
     * Returns number of rows deleted.
     */
    public function delete(int $notificationId, int $customerId): int {
        $query = "DELETE FROM " . $this->table . "
                  WHERE NotificationID = ? AND CustomerID = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$notificationId, $customerId]);
        return $stmt->rowCount();
    }
}
