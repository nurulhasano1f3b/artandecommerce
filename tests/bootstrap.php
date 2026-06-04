<?php

/**
 * PHPUnit bootstrap — loads application classes before any test runs.
 * The test database must be "ecommerce_db" (same as dev) with the
 * Notifications migration already applied.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../services/NotificationService.php';
require_once __DIR__ . '/../controllers/NotificationController.php';
