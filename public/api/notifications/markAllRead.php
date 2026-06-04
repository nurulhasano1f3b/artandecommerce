<?php
/**
 * POST /public/api/notifications/markAllRead.php
 *
 * Marks all notifications for the session customer as read.
 *
 * Response: JSON  { "success": true, "rows_updated": 5 }
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
require_once __DIR__ . "/../../../config/session.php"; // M-02

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . "/../../../controllers/NotificationController.php";

$customerId = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : 0;

if ($customerId < 1) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No active customer session.']);
    exit;
}

$controller = new NotificationController();
echo json_encode($controller->markAllRead($customerId));
