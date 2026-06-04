<?php

use PHPUnit\Framework\TestCase;

/**
 * NotificationTest
 *
 * Integration tests for NotificationService + Notification model.
 * Tests run against the live ecommerce_db database.
 *
 * Two isolated test customers are created in setUpBeforeClass and
 * deleted (cascading to their notifications) in tearDownAfterClass.
 * Each test starts with a clean Notifications table for those customers.
 *
 * Run:  ./vendor/bin/phpunit
 */
class NotificationTest extends TestCase {

    private static PDO $db;
    private static int $customer1;
    private static int $customer2;

    private NotificationService $service;

    // -------------------------------------------------------------------------
    // Fixture setup / teardown
    // -------------------------------------------------------------------------

    public static function setUpBeforeClass(): void {
        $database = new Database();
        self::$db = $database->connect();

        $insert = "INSERT INTO Customers
                   (Email, FirstName, LastName, Title, Address, City, State, Country, PostCode, Phone)
                   VALUES (?, 'Test', 'User', 'Mr.', '1 Test St', 'Darwin', 'NT', 'Australia', '0800', '0400000000')";

        $stmt = self::$db->prepare($insert);
        $stmt->execute(['notification_test_1@test.invalid']);
        self::$customer1 = (int)self::$db->lastInsertId();

        $stmt->execute(['notification_test_2@test.invalid']);
        self::$customer2 = (int)self::$db->lastInsertId();
    }

    public static function tearDownAfterClass(): void {
        // ON DELETE CASCADE removes Notifications rows automatically.
        $stmt = self::$db->prepare("DELETE FROM Customers WHERE CustomerID IN (?, ?)");
        $stmt->execute([self::$customer1, self::$customer2]);
    }

    protected function setUp(): void {
        // Wipe both customers' notifications (and broadcasts) before each test.
        $stmt = self::$db->prepare(
            "DELETE FROM Notifications WHERE CustomerID IN (?, ?) OR CustomerID IS NULL"
        );
        $stmt->execute([self::$customer1, self::$customer2]);

        $this->service = new NotificationService();
    }

    // -------------------------------------------------------------------------
    // notifyOrderPlaced
    // -------------------------------------------------------------------------

    public function testNotifyOrderPlacedReturnsPositiveId(): void {
        $id = $this->service->notifyOrderPlaced(self::$customer1, 99);

        $this->assertGreaterThan(0, $id);
    }

    public function testNotifyOrderPlacedCreatesCorrectRow(): void {
        $this->service->notifyOrderPlaced(self::$customer1, 42);

        $rows = $this->service->getForCustomer(self::$customer1);

        $this->assertCount(1, $rows);
        $this->assertEquals('order_placed',            $rows[0]['Type']);
        $this->assertEquals('Order Confirmed',         $rows[0]['Title']);
        $this->assertStringContainsString('42',        $rows[0]['Message']);
        $this->assertEquals(0,                         $rows[0]['IsRead']);
        $this->assertNull($rows[0]['ReadAt']);
    }

    // -------------------------------------------------------------------------
    // notifyNewArtwork — broadcast (CustomerID = null)
    // -------------------------------------------------------------------------

    public function testBroadcastNotificationIsVisibleToAnyCustomer(): void {
        $this->service->notifyNewArtwork('Sunset Landscape', 'Painting');

        $c1Rows = $this->service->getForCustomer(self::$customer1);
        $c2Rows = $this->service->getForCustomer(self::$customer2);

        $this->assertCount(1, $c1Rows);
        $this->assertCount(1, $c2Rows);
        $this->assertEquals('new_artwork', $c1Rows[0]['Type']);
        $this->assertNull($c1Rows[0]['CustomerID']);
    }

    // -------------------------------------------------------------------------
    // getForCustomer / getUnreadForCustomer — isolation between customers
    // -------------------------------------------------------------------------

    public function testGetForCustomerDoesNotReturnOtherCustomersNotifications(): void {
        $this->service->notifyOrderPlaced(self::$customer1, 1);
        $this->service->notifyOrderPlaced(self::$customer2, 2);

        $rows = $this->service->getForCustomer(self::$customer1);

        $this->assertCount(1, $rows);
        $this->assertEquals(self::$customer1, (int)$rows[0]['CustomerID']);
    }

    public function testGetForCustomerReturnsMultipleNotificationsNewestFirst(): void {
        $this->service->notifyOrderPlaced(self::$customer1, 10);
        $this->service->notifyOrderPlaced(self::$customer1, 11);
        $this->service->notifyOrderPlaced(self::$customer1, 12);

        $rows = $this->service->getForCustomer(self::$customer1);

        $this->assertCount(3, $rows);
        // Newest first: purchase 12 should appear before 10
        $this->assertStringContainsString('12', $rows[0]['Message']);
        $this->assertStringContainsString('10', $rows[2]['Message']);
    }

    public function testGetUnreadForCustomerExcludesReadNotifications(): void {
        $id = $this->service->notifyOrderPlaced(self::$customer1, 1);
        $this->service->notifyOrderPlaced(self::$customer1, 2);
        $this->service->markAsRead($id, self::$customer1);

        $unread = $this->service->getUnreadForCustomer(self::$customer1);

        $this->assertCount(1, $unread);
        $this->assertStringContainsString('#2', $unread[0]['Message']);
    }

    // -------------------------------------------------------------------------
    // getUnreadCount
    // -------------------------------------------------------------------------

    public function testGetUnreadCountReturnsZeroForNewCustomer(): void {
        $this->assertEquals(0, $this->service->getUnreadCount(self::$customer1));
    }

    public function testGetUnreadCountReflectsMultipleNotifications(): void {
        $this->service->notifyOrderPlaced(self::$customer1, 1);
        $this->service->notifyOrderPlaced(self::$customer1, 2);
        $this->service->notifyOrderPlaced(self::$customer1, 3);

        $this->assertEquals(3, $this->service->getUnreadCount(self::$customer1));
    }

    public function testGetUnreadCountDecreasesAfterMarkRead(): void {
        $id = $this->service->notifyOrderPlaced(self::$customer1, 1);
        $this->service->notifyOrderPlaced(self::$customer1, 2);

        $this->service->markAsRead($id, self::$customer1);

        $this->assertEquals(1, $this->service->getUnreadCount(self::$customer1));
    }

    // -------------------------------------------------------------------------
    // markAsRead
    // -------------------------------------------------------------------------

    public function testMarkAsReadReturnsTrueOnSuccess(): void {
        $id = $this->service->notifyOrderPlaced(self::$customer1, 1);

        $result = $this->service->markAsRead($id, self::$customer1);

        $this->assertTrue($result);
    }

    public function testMarkAsReadSetsIsReadAndReadAt(): void {
        $id = $this->service->notifyOrderPlaced(self::$customer1, 1);
        $this->service->markAsRead($id, self::$customer1);

        $stmt = self::$db->prepare("SELECT IsRead, ReadAt FROM Notifications WHERE NotificationID = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals(1, (int)$row['IsRead']);
        $this->assertNotNull($row['ReadAt']);
    }

    public function testMarkAsReadReturnsFalseIfAlreadyRead(): void {
        $id = $this->service->notifyOrderPlaced(self::$customer1, 1);
        $this->service->markAsRead($id, self::$customer1);

        $result = $this->service->markAsRead($id, self::$customer1);

        $this->assertFalse($result);
    }

    public function testMarkAsReadCannotMarkAnotherCustomersNotification(): void {
        $id = $this->service->notifyOrderPlaced(self::$customer1, 1);

        // customer2 attempts to mark customer1's notification
        $result = $this->service->markAsRead($id, self::$customer2);

        $this->assertFalse($result);
        $this->assertEquals(1, $this->service->getUnreadCount(self::$customer1));
    }

    public function testMarkAsReadReturnsFalseForInvalidId(): void {
        $this->assertFalse($this->service->markAsRead(0, self::$customer1));
        $this->assertFalse($this->service->markAsRead(-1, self::$customer1));
    }

    // -------------------------------------------------------------------------
    // markAllAsRead
    // -------------------------------------------------------------------------

    public function testMarkAllAsReadReturnsRowCount(): void {
        $this->service->notifyOrderPlaced(self::$customer1, 1);
        $this->service->notifyOrderPlaced(self::$customer1, 2);
        $this->service->notifyOrderPlaced(self::$customer1, 3);

        $updated = $this->service->markAllAsRead(self::$customer1);

        $this->assertEquals(3, $updated);
    }

    public function testMarkAllAsReadDoesNotAffectOtherCustomers(): void {
        $this->service->notifyOrderPlaced(self::$customer1, 1);
        $this->service->notifyOrderPlaced(self::$customer2, 2);

        $this->service->markAllAsRead(self::$customer1);

        $this->assertEquals(0, $this->service->getUnreadCount(self::$customer1));
        $this->assertEquals(1, $this->service->getUnreadCount(self::$customer2));
    }

    public function testMarkAllAsReadReturnsZeroWhenNothingToMark(): void {
        $updated = $this->service->markAllAsRead(self::$customer1);

        $this->assertEquals(0, $updated);
    }

    // -------------------------------------------------------------------------
    // deleteNotification
    // -------------------------------------------------------------------------

    public function testDeleteNotificationRemovesRow(): void {
        $id = $this->service->notifyOrderPlaced(self::$customer1, 1);

        $result = $this->service->deleteNotification($id, self::$customer1);

        $this->assertTrue($result);
        $this->assertCount(0, $this->service->getForCustomer(self::$customer1));
    }

    public function testDeleteNotificationReturnsTrueOnSuccess(): void {
        $id = $this->service->notifyOrderPlaced(self::$customer1, 1);

        $this->assertTrue($this->service->deleteNotification($id, self::$customer1));
    }

    public function testDeleteNotificationCannotDeleteAnotherCustomersNotification(): void {
        $id = $this->service->notifyOrderPlaced(self::$customer1, 1);

        $result = $this->service->deleteNotification($id, self::$customer2);

        $this->assertFalse($result);
        $this->assertCount(1, $this->service->getForCustomer(self::$customer1));
    }

    public function testDeleteNotificationReturnsFalseForInvalidId(): void {
        $this->assertFalse($this->service->deleteNotification(0, self::$customer1));
        $this->assertFalse($this->service->deleteNotification(99999999, self::$customer1));
    }

    // -------------------------------------------------------------------------
    // notifyOrderUpdate
    // -------------------------------------------------------------------------

    public function testNotifyOrderUpdateCreatesCorrectRow(): void {
        $this->service->notifyOrderUpdate(self::$customer1, 7, 'Shipped');

        $rows = $this->service->getForCustomer(self::$customer1);

        $this->assertCount(1, $rows);
        $this->assertEquals('order_update', $rows[0]['Type']);
        $this->assertStringContainsString('7',        $rows[0]['Message']);
        $this->assertStringContainsString('Shipped',  $rows[0]['Message']);
    }
}
