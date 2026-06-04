<?php
/**
 * GET /public/api/notifications/getNotifications.php
 *
 * Returns all notifications for the session customer.
 * Append ?unread=1 to return only unread notifications.
 *
 * Response: JSON
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
require_once __DIR__ . "/../../../config/session.php"; // M-02

require_once __DIR__ . "/../../../controllers/NotificationController.php";

$customerId = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : 0;

if ($customerId < 1) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No active customer session.']);
    exit;
}

$controller = new NotificationController();

if (isset($_GET['unread']) && $_GET['unread'] === '1') {
    $response = $controller->getUnread($customerId);
} else {
    $response = $controller->getAll($customerId);
}

echo json_encode($response);
