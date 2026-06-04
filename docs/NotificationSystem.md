# Notification System

Implements in-app notifications for the Art & E-Commerce platform. Customers receive
notifications when they place an order and can see any broadcast announcements
(e.g. new artwork added). Notifications are stored in MySQL and exposed via a
small JSON API.

---

## Feature Overview

| Event | Trigger location | Type | Audience |
|---|---|---|---|
| Order placed | `public/processCheckout.php` | `order_placed` | Purchasing customer |
| New artwork added | Call `NotificationService::notifyNewArtwork()` | `new_artwork` | Broadcast (all customers) |
| Order status update | Call `NotificationService::notifyOrderUpdate()` | `order_update` | Specific customer |

---

## Architecture

The implementation adds three new layers following the existing MVC structure:

```
Request
  └─► public/api/notifications/*.php   (API endpoint — HTTP + JSON)
          └─► controllers/NotificationController.php  (input validation, response shape)
                  └─► services/NotificationService.php  (business rules, event triggers)
                          └─► models/Notification.php  (SQL queries via PDO)
                                  └─► config/database.php  (PDO connection)
```

**New directories:**

| Directory | Purpose |
|---|---|
| `services/` | Service layer — sits between controllers and models |
| `public/api/notifications/` | JSON API endpoint scripts |
| `database/migrations/` | Incremental schema changes |
| `tests/` | PHPUnit integration tests |

---

## Database Migration

**File:** `database/migrations/001_create_notifications.sql`

Run once against `ecommerce_db` after the base schema is in place:

```bash
mysql -u root -p ecommerce_db < database/migrations/001_create_notifications.sql
```

### `Notifications` table

| Column | Type | Description |
|---|---|---|
| `NotificationID` | `INT AUTO_INCREMENT PK` | Surrogate key |
| `CustomerID` | `INT FK → Customers` | Owner; `NULL` = broadcast to all customers |
| `Type` | `ENUM` | `order_placed`, `new_artwork`, `order_update` |
| `Title` | `VARCHAR(255)` | Short heading shown in notification UI |
| `Message` | `TEXT` | Full notification body |
| `IsRead` | `TINYINT(1)` | `0` = unread, `1` = read |
| `CreatedAt` | `DATETIME` | Set to `NOW()` on insert |
| `ReadAt` | `DATETIME` | Set when `IsRead` flipped to `1`; `NULL` if unread |

**Indexes:**

| Index | Columns | Reason |
|---|---|---|
| `idx_notifications_customer_read` | `(CustomerID, IsRead)` | Main read path — look up a customer's unread notifications |
| `idx_notifications_created` | `(CreatedAt)` | Chronological ordering |

**FK behaviour:** `ON DELETE CASCADE` — deleting a `Customers` row removes all their notifications automatically.

---

## File Reference

### Model — `models/Notification.php`

| Method | Parameters | Returns | Description |
|---|---|---|---|
| `create` | `$customerId\|null, $type, $title, $message` | `int` — new ID | Inserts a notification row |
| `getAllByCustomerId` | `int $customerId` | `array` | All notifications (incl. broadcasts), newest first |
| `getUnreadByCustomerId` | `int $customerId` | `array` | Unread only |
| `getUnreadCount` | `int $customerId` | `int` | Count of unread |
| `markAsRead` | `int $notificationId, int $customerId` | `int` rows updated | Enforces ownership via `WHERE CustomerID = ?` |
| `markAllAsRead` | `int $customerId` | `int` rows updated | Marks all unread for a customer |
| `getById` | `int $notificationId` | `array\|null` | Single row fetch |
| `delete` | `int $notificationId, int $customerId` | `int` rows deleted | Customer-owned only; broadcasts cannot be deleted |

---

### Service — `services/NotificationService.php`

**Event triggers:**

| Method | When to call |
|---|---|
| `notifyOrderPlaced(int $customerId, int $purchaseId)` | After a successful checkout |
| `notifyNewArtwork(string $title, string $category, ?int $customerId = null)` | After a new product is saved (null = broadcast) |
| `notifyOrderUpdate(int $customerId, int $purchaseId, string $status)` | When order status changes |

**Queries:**

| Method | Description |
|---|---|
| `getForCustomer(int $customerId)` | All notifications |
| `getUnreadForCustomer(int $customerId)` | Unread only |
| `getUnreadCount(int $customerId)` | Integer count |

**Mutations:**

| Method | Returns | Notes |
|---|---|---|
| `markAsRead(int $notificationId, int $customerId)` | `bool` | `false` if not owned, not found, or already read |
| `markAllAsRead(int $customerId)` | `int` rows updated | — |
| `deleteNotification(int $notificationId, int $customerId)` | `bool` | `false` if not owned or not found |

---

### Controller — `controllers/NotificationController.php`

Wraps service calls and returns structured arrays ready for `json_encode`:

```php
// Success shape
['success' => true, 'data' => [...], 'count' => 3]

// Error shape
['success' => false, 'error' => 'Notification not found or already read.']
```

---

## API Endpoints

All endpoints require an active PHP session with `$_SESSION['customer_id']` set.
This value is stored automatically when a customer completes checkout.

### `GET /public/api/notifications/getNotifications.php`

Returns all notifications for the session customer.

**Query params:**

| Param | Value | Effect |
|---|---|---|
| `unread` | `1` | Return only unread notifications |

**Example response:**
```json
{
    "success": true,
    "count": 2,
    "data": [
        {
            "NotificationID": "5",
            "CustomerID": "12",
            "Type": "order_placed",
            "Title": "Order Confirmed",
            "Message": "Your order #47 has been placed successfully. Thank you for your purchase!",
            "IsRead": "0",
            "CreatedAt": "2026-06-04 14:30:00",
            "ReadAt": null
        },
        {
            "NotificationID": "3",
            "CustomerID": null,
            "Type": "new_artwork",
            "Title": "New Artwork Available",
            "Message": "A new Painting has just been added to the gallery: \"Sunset Landscape\".",
            "IsRead": "0",
            "CreatedAt": "2026-06-04 09:00:00",
            "ReadAt": null
        }
    ]
}
```

---

### `GET /public/api/notifications/getUnreadCount.php`

Returns the unread badge count. Designed for polling from a frontend indicator.

**Example response:**
```json
{
    "success": true,
    "unread_count": 2
}
```

---

### `POST /public/api/notifications/markRead.php`

Marks a single notification as read.

**POST body:**

| Field | Type | Required |
|---|---|---|
| `notification_id` | `int` | Yes |

**Example response:**
```json
{
    "success": true,
    "message": "Notification marked as read."
}
```

**Error (not owned / already read):**
```json
{
    "success": false,
    "error": "Notification not found or already read."
}
```

---

### `POST /public/api/notifications/markAllRead.php`

Marks all notifications for the session customer as read.

**Example response:**
```json
{
    "success": true,
    "message": "All notifications marked as read.",
    "rows_updated": 3
}
```

---

## Session Flow

```
1. Customer completes checkout
       └─► processCheckout.php
               ├── NotificationService::notifyOrderPlaced($customerId, $purchaseId)
               │       └─► Inserts row in Notifications table
               └── $_SESSION['customer_id'] = $customerId

2. Customer's browser polls for unread count
       └─► GET /public/api/notifications/getUnreadCount.php
               └─► reads $_SESSION['customer_id']
               └─► returns { "unread_count": 1 }

3. Customer opens notification panel
       └─► GET /public/api/notifications/getNotifications.php
               └─► returns full notification list

4. Customer clicks a notification
       └─► POST /public/api/notifications/markRead.php
               body: notification_id=5
               └─► returns { "success": true }
```

---

## Running the Tests

### First-time setup

```bash
# From the project root
composer install
```

### Run all tests

```bash
./vendor/bin/phpunit
```

### Expected output

```
PHPUnit 11.x.x

NotificationTest
  ✓ testNotifyOrderPlacedReturnsPositiveId
  ✓ testNotifyOrderPlacedCreatesCorrectRow
  ✓ testBroadcastNotificationIsVisibleToAnyCustomer
  ✓ testGetForCustomerDoesNotReturnOtherCustomersNotifications
  ✓ testGetForCustomerReturnsMultipleNotificationsNewestFirst
  ✓ testGetUnreadForCustomerExcludesReadNotifications
  ✓ testGetUnreadCountReturnsZeroForNewCustomer
  ✓ testGetUnreadCountReflectsMultipleNotifications
  ✓ testGetUnreadCountDecreasesAfterMarkRead
  ✓ testMarkAsReadReturnsTrueOnSuccess
  ✓ testMarkAsReadSetsIsReadAndReadAt
  ✓ testMarkAsReadReturnsFalseIfAlreadyRead
  ✓ testMarkAsReadCannotMarkAnotherCustomersNotification
  ✓ testMarkAsReadReturnsFalseForInvalidId
  ✓ testMarkAllAsReadReturnsRowCount
  ✓ testMarkAllAsReadDoesNotAffectOtherCustomers
  ✓ testMarkAllAsReadReturnsZeroWhenNothingToMark
  ✓ testDeleteNotificationRemovesRow
  ✓ testDeleteNotificationReturnsTrueOnSuccess
  ✓ testDeleteNotificationCannotDeleteAnotherCustomersNotification
  ✓ testDeleteNotificationReturnsFalseForInvalidId
  ✓ testNotifyOrderUpdateCreatesCorrectRow

Time: 00:00.xyz, Memory: x.xx MB

OK (22 tests, XX assertions)
```

### What the tests cover

| Area | Tests |
|---|---|
| Notification creation | ID returned, correct Type/Title/Message, IsRead defaults to 0 |
| Broadcast visibility | NULL CustomerID row appears for all customers |
| Customer isolation | Each customer sees only their own rows |
| Ordering | Newest notification returned first |
| Unread count | Zero baseline, increments, decrements after mark-read |
| markAsRead | Sets IsRead + ReadAt, ownership enforced, idempotent |
| markAllAsRead | Correct row count, does not affect other customers |
| delete | Row removed, ownership enforced, invalid ID handled |

---

## Known Limitations

| Limitation | Impact |
|---|---|
| Broadcast read state is global | If Customer A marks a broadcast as read, it remains marked as read — there is no per-customer read tracking for broadcast rows. A `NotificationReads` junction table would fix this. |
| `$_SESSION['customer_id']` is set at checkout | A customer who visits without checking out has no session ID and cannot retrieve notifications. |
| No real-time push | Notifications are polled. A WebSocket or Server-Sent Events layer would be needed for live delivery. |
| New artwork broadcasts must be triggered manually | There is no admin panel; `notifyNewArtwork()` must be called from code when a product is inserted. |
