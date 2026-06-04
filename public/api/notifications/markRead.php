<?php
/**
 * POST /public/api/notifications/markRead.php
 *
 * Marks a single notification as read.
 *
 * Request body (POST):
 *   notification_id  int  required
 *
 * Response: JSON  { "success": true, "message": "..." }
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

$customerId     = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : 0;
$notificationId = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;

if ($customerId < 1) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No active customer session.']);
    exit;
}

if ($notificationId < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'notification_id is required.']);
    exit;
}

$controller = new NotificationController();
echo json_encode($controller->markRead($notificationId, $customerId));
