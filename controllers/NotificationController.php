<?php

require_once __DIR__ . "/../services/NotificationService.php";

/**
 * NotificationController
 *
 * Bridges API endpoint scripts and the NotificationService.
 * Handles input validation and shapes the response payload.
 */
class NotificationController {

    private $service;

    public function __construct() {
        $this->service = new NotificationService();
    }

    /**
     * GET all notifications for the session customer.
     * Returns an array response ready for json_encode.
     */
    public function getAll(int $customerId): array {
        $notifications = $this->service->getForCustomer($customerId);
        return [
            'success' => true,
            'data'    => $notifications,
            'count'   => count($notifications),
        ];
    }

    /**
     * GET unread notifications only.
     */
    public function getUnread(int $customerId): array {
        $notifications = $this->service->getUnreadForCustomer($customerId);
        return [
            'success' => true,
            'data'    => $notifications,
            'count'   => count($notifications),
        ];
    }

    /**
     * GET count of unread notifications.
     */
    public function getUnreadCount(int $customerId): array {
        return [
            'success'      => true,
            'unread_count' => $this->service->getUnreadCount($customerId),
        ];
    }

    /**
     * POST mark a single notification as read.
     */
    public function markRead(int $notificationId, int $customerId): array {
        if ($notificationId < 1) {
            return ['success' => false, 'error' => 'Invalid notification ID.'];
        }

        $updated = $this->service->markAsRead($notificationId, $customerId);

        if (!$updated) {
            return ['success' => false, 'error' => 'Notification not found or already read.'];
        }

        return ['success' => true, 'message' => 'Notification marked as read.'];
    }

    /**
     * POST mark all notifications as read.
     */
    public function markAllRead(int $customerId): array {
        $count = $this->service->markAllAsRead($customerId);
        return [
            'success'      => true,
            'message'      => 'All notifications marked as read.',
            'rows_updated' => $count,
        ];
    }

    /**
     * DELETE a notification.
     */
    public function delete(int $notificationId, int $customerId): array {
        if ($notificationId < 1) {
            return ['success' => false, 'error' => 'Invalid notification ID.'];
        }

        $deleted = $this->service->deleteNotification($notificationId, $customerId);

        if (!$deleted) {
            return ['success' => false, 'error' => 'Notification not found or not owned by you.'];
        }

        return ['success' => true, 'message' => 'Notification deleted.'];
    }
}
