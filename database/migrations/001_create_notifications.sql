-- Migration 001: Create Notifications table
-- Run this after the base schema (database/create.sql) is in place.

USE ecommerce_db;

CREATE TABLE IF NOT EXISTS Notifications (
    NotificationID INT          NOT NULL AUTO_INCREMENT,
    CustomerID     INT          DEFAULT NULL,           -- NULL = broadcast to all customers
    Type           ENUM('order_placed','new_artwork','order_update') NOT NULL,
    Title          VARCHAR(255) NOT NULL,
    Message        TEXT         NOT NULL,
    IsRead         TINYINT(1)   NOT NULL DEFAULT 0,
    CreatedAt      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ReadAt         DATETIME     DEFAULT NULL,

    PRIMARY KEY (NotificationID),

    -- Lookup: all notifications for a customer (most common read path)
    INDEX idx_notifications_customer_read (CustomerID, IsRead),

    -- Lookup: chronological feed
    INDEX idx_notifications_created (CreatedAt),

    CONSTRAINT fk_notification_customer
        FOREIGN KEY (CustomerID)
        REFERENCES Customers(CustomerID)
        ON DELETE CASCADE
        ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
