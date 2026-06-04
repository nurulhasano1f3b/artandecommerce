<?php

require_once __DIR__ . "/../models/Notification.php";

/**
 * NotificationService
 *
 * Service layer between controllers and the Notification model.
 * Owns the business rules around what triggers a notification,
 * what the message content should be, and ownership enforcement.
 */
class NotificationService {

    private $model;

    public function __construct() {
        $this->model = new Notification();
    }

    // -------------------------------------------------------------------------
    // Triggers — called by other parts of the app when an event occurs
    // -------------------------------------------------------------------------

    /**
     * Fired when a customer completes checkout.
     */
    public function notifyOrderPlaced(int $customerId, int $purchaseId): int {
        return $this->model->create(
            $customerId,
            'order_placed',
            'Order Confirmed',
            "Your order #" . $purchaseId . " has been placed successfully. Thank you for your purchase!"
        );
    }

    /**
     * Fired when a new artwork product is added to the catalogue.
     * Passing null creates a broadcast visible to all customers.
     */
    public function notifyNewArtwork(string $title, string $category, ?int $customerId = null): int {
        return $this->model->create(
            $customerId,
            'new_artwork',
            'New Artwork Available',
            "A new " . $category . " has just been added to the gallery: \"" . $title . "\"."
        );
    }

    /**
     * Fired when an order status changes (e.g., shipped, cancelled).
     */
    public function notifyOrderUpdate(int $customerId, int $purchaseId, string $status): int {
        return $this->model->create(
            $customerId,
            'order_update',
            'Order Update',
            "Your order #" . $purchaseId . " status has been updated: " . $status . "."
        );
    }

    // -------------------------------------------------------------------------
    // Queries — called by the controller to read notifications
    // -------------------------------------------------------------------------

    /**
     * All notifications for a customer (read and unread), newest first.
     */
    public function getForCustomer(int $customerId): array {
        return $this->model->getAllByCustomerId($customerId);
    }

    /**
     * Only unread notifications for a customer.
     */
    public function getUnreadForCustomer(int $customerId): array {
        return $this->model->getUnreadByCustomerId($customerId);
    }

    /**
     * Count of unread notifications.
     */
    public function getUnreadCount(int $customerId): int {
        return $this->model->getUnreadCount($customerId);
    }

    // -------------------------------------------------------------------------
    // Mutations — called by the controller to update notification state
    // -------------------------------------------------------------------------

    /**
     * Mark a single notification as read.
     * Returns false if the notification does not exist, is not owned by
     * this customer, or is already read.
     */
    public function markAsRead(int $notificationId, int $customerId): bool {
        if ($notificationId < 1 || $customerId < 1) {
            return false;
        }
        return $this->model->markAsRead($notificationId, $customerId) > 0;
    }

    /**
     * Mark every notification for a customer as read.
     * Returns the number of rows updated.
     */
    public function markAllAsRead(int $customerId): int {
        if ($customerId < 1) {
            return 0;
        }
        return $this->model->markAllAsRead($customerId);
    }

    /**
     * Delete a customer-owned notification.
     * Returns false if the notification does not exist or is not owned by
     * this customer. Broadcasts cannot be deleted by customers.
     */
    public function deleteNotification(int $notificationId, int $customerId): bool {
        if ($notificationId < 1 || $customerId < 1) {
            return false;
        }
        return $this->model->delete($notificationId, $customerId) > 0;
    }
}
