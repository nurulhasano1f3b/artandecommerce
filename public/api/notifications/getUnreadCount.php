<?php
/**
 * GET /public/api/notifications/getUnreadCount.php
 *
 * Returns the unread notification count for the session customer.
 * Intended for badge/indicator polling.
 *
 * Response: JSON  { "success": true, "unread_count": 3 }
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
echo json_encode($controller->getUnreadCount($customerId));
