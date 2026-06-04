# Database Documentation

**Database name:** `ecommerce_db`  
**Engine:** MySQL (InnoDB)  
**Character set:** utf8mb4  
**Schema file:** `database/create.sql`  
**Seed data file:** `database/load.sql`

---

## Entity-Relationship Overview

```
Customers ──────────────── Purchases ──────────────── PurchaseItem ──── Products
    │                           │                           │                 │
 CustomerID (PK)           PurchaseID (PK)           PurchaseID (FK)    ProductID (PK)
 Email                     CustomerID (FK) ──────────►Customers         Title
 FirstName                 PurchaseDate               ProductID  (FK) ──►Products
 LastName                                             Quantity
 Title
 Address
 City
 State
 Country
 PostCode
 Phone
```

**Relationships:**

| Relationship | Cardinality | Description |
|---|---|---|
| Customers → Purchases | One-to-Many | One customer can have many purchases |
| Purchases → PurchaseItem | One-to-Many | One purchase contains one or more line items |
| Products → PurchaseItem | One-to-Many | One product can appear in many purchase items |
| Purchases ↔ Products (via PurchaseItem) | Many-to-Many | Bridge table with `Quantity` attribute |

---

## Tables

### `Customers`

Stores customer information collected at checkout.

| Column | Type | Constraints | Description |
|---|---|---|---|
| `CustomerID` | `INT` | `PRIMARY KEY`, `AUTO_INCREMENT`, `NOT NULL` | Unique customer identifier |
| `Email` | `VARCHAR(255)` | `NOT NULL` | Customer email address |
| `FirstName` | `VARCHAR(50)` | `NOT NULL` | Given name |
| `LastName` | `VARCHAR(50)` | `NOT NULL` | Family name |
| `Title` | `ENUM('Mr.','Mrs.','Mx.','Ms.')` | nullable | Honorific/salutation |
| `Address` | `VARCHAR(75)` | `NOT NULL` | Street address |
| `City` | `VARCHAR(50)` | `NOT NULL` | City or suburb |
| `State` | `VARCHAR(50)` | `NOT NULL` | State or territory |
| `Country` | `VARCHAR(50)` | `NOT NULL` | Country |
| `PostCode` | `VARCHAR(10)` | `NOT NULL` | Postal/ZIP code (VARCHAR to preserve leading zeros) |
| `Phone` | `VARCHAR(15)` | `NOT NULL` | Phone number (VARCHAR to allow spaces and area codes) |

**Notes:**
- `PostCode` is `VARCHAR` rather than `INT` to preserve leading zeros (e.g., Australian postcode `0800`).
- `Phone` is `VARCHAR(15)` to allow formatting with spaces (e.g., `0412 345 678`).
- No login credentials — the application does not have user accounts.

---

### `Products`

Stores the artwork items available for sale.

| Column | Type | Constraints | Description |
|---|---|---|---|
| `ProductID` | `INT` | `PRIMARY KEY`, `AUTO_INCREMENT`, `NOT NULL` | Unique product identifier |
| `Title` | `VARCHAR(100)` | `NOT NULL` | Display name of the artwork |
| `Description` | `TEXT` | `NOT NULL` | Full description |
| `Price` | `DECIMAL(10,2)` | `NOT NULL` | Price in the store's currency (e.g., `199.99`) |
| `Category` | `ENUM('Painting','Sculpture','Drawing')` | `NOT NULL` | Artwork category |
| `Image` | `VARCHAR(255)` | `DEFAULT 'placeholder.jpg'` | Filename of the product image (not binary data) |

**Notes:**
- `Price` uses `DECIMAL(10,2)` for accurate monetary arithmetic (no floating-point rounding errors).
- `Image` stores only the filename; the web server must serve the actual image file from a known directory.
- `Category` is a closed enum — adding a new category requires an `ALTER TABLE`.

---

### `Purchases`

Records each completed order.

| Column | Type | Constraints | Description |
|---|---|---|---|
| `PurchaseID` | `INT` | `PRIMARY KEY`, `AUTO_INCREMENT`, `NOT NULL` | Unique order identifier |
| `CustomerID` | `INT` | `NOT NULL`, `FOREIGN KEY → Customers(CustomerID)` | The customer who placed the order |
| `PurchaseDate` | `DATETIME` | `NOT NULL` | Timestamp when the order was processed (`NOW()`) |

**Foreign keys:**

| Constraint | Column | References |
|---|---|---|
| `fk_Customer` | `CustomerID` | `Customers(CustomerID)` |

---

### `PurchaseItem`

Bridge/junction table between `Purchases` and `Products`. Each row represents one product line in an order.

| Column | Type | Constraints | Description |
|---|---|---|---|
| `PurchaseID` | `INT` | `NOT NULL`, part of composite PK, `FOREIGN KEY → Purchases(PurchaseID)` | The parent purchase |
| `ProductID` | `INT` | `NOT NULL`, part of composite PK, `FOREIGN KEY → Products(ProductID)` | The product purchased |
| `Quantity` | `INT(5)` | `NOT NULL` | How many units of this product were ordered |

**Primary key:** Composite `(PurchaseID, ProductID)` — ensures the same product cannot appear twice in the same purchase.

**Foreign keys:**

| Constraint | Column | References |
|---|---|---|
| `fk_Purchase` | `PurchaseID` | `Purchases(PurchaseID)` |
| `fk_Product` | `ProductID` | `Products(ProductID)` |

---

## Schema SQL

```sql
USE ecommerce_db;

DROP TABLE IF EXISTS Purchases;
DROP TABLE IF EXISTS PurchaseItem;
DROP TABLE IF EXISTS Products;
DROP TABLE IF EXISTS Customers;

CREATE TABLE Customers (
    CustomerID INT NOT NULL AUTO_INCREMENT,
    Email      VARCHAR(255) NOT NULL,
    FirstName  VARCHAR(50)  NOT NULL,
    LastName   VARCHAR(50)  NOT NULL,
    Title      ENUM("Mr.","Mrs.","Mx.","Ms."),
    Address    VARCHAR(75)  NOT NULL,
    City       VARCHAR(50)  NOT NULL,
    State      VARCHAR(50)  NOT NULL,
    Country    VARCHAR(50)  NOT NULL,
    PostCode   VARCHAR(10)  NOT NULL,
    Phone      VARCHAR(15)  NOT NULL,
    PRIMARY KEY (CustomerID)
);

CREATE TABLE Products (
    ProductID   INT          NOT NULL AUTO_INCREMENT,
    Title       VARCHAR(100) NOT NULL,
    Description TEXT         NOT NULL,
    Price       DECIMAL(10,2) NOT NULL,
    Category    ENUM("Painting","Sculpture","Drawing") NOT NULL,
    Image       VARCHAR(255) DEFAULT 'placeholder.jpg',
    PRIMARY KEY (ProductID)
);

CREATE TABLE Purchases (
    PurchaseID   INT      NOT NULL AUTO_INCREMENT,
    CustomerID   INT      NOT NULL,
    PurchaseDate DATETIME NOT NULL,
    PRIMARY KEY (PurchaseID),
    CONSTRAINT fk_Customer
        FOREIGN KEY (CustomerID) REFERENCES Customers(CustomerID)
);

CREATE TABLE PurchaseItem (
    PurchaseID INT     NOT NULL,
    ProductID  INT     NOT NULL,
    Quantity   INT(5)  NOT NULL,
    PRIMARY KEY (PurchaseID, ProductID),
    CONSTRAINT fk_Purchase
        FOREIGN KEY (PurchaseID) REFERENCES Purchases(PurchaseID),
    CONSTRAINT fk_Product
        FOREIGN KEY (ProductID)  REFERENCES Products(ProductID)
);
```

---

## Seed Data

`database/load.sql` inserts the following sample records for development and testing:

**Customers (1 row)**

| CustomerID | Email | Name | Address |
|---|---|---|---|
| 1 | johndoetest@example.com | Mr. John Doe | 24 Faker Avenue, Darwin NT 0800, Australia |

**Products (3 rows)**

| ProductID | Title | Price | Category |
|---|---|---|---|
| 1 | Sunset Landscape | $199.99 | Painting |
| 2 | Ocean Sculpture | $349.50 | Sculpture |
| 3 | Abstract Sketch | $89.99 | Drawing |

**Purchases (1 row)**

| PurchaseID | CustomerID | PurchaseDate |
|---|---|---|
| 1 | 1 | (NOW() at import time) |

**PurchaseItem (2 rows)**

| PurchaseID | ProductID | Quantity |
|---|---|---|
| 1 | 1 | 1 |
| 1 | 2 | 2 |

---

## Common Queries

```sql
-- All products
SELECT * FROM Products;

-- Single product by ID
SELECT * FROM Products WHERE ProductID = 1;

-- Most recently added product
SELECT * FROM Products ORDER BY ProductID DESC LIMIT 1;

-- All purchases for a customer
SELECT p.PurchaseID, p.PurchaseDate
FROM Purchases p
WHERE p.CustomerID = 1;

-- Full order breakdown with product names
SELECT
    pi.PurchaseID,
    pr.Title,
    pr.Price,
    pi.Quantity,
    (pr.Price * pi.Quantity) AS LineTotal
FROM PurchaseItem pi
JOIN Products pr ON pi.ProductID = pr.ProductID
WHERE pi.PurchaseID = 1;

-- Order total
SELECT
    pi.PurchaseID,
    SUM(pr.Price * pi.Quantity) AS OrderTotal
FROM PurchaseItem pi
JOIN Products pr ON pi.ProductID = pr.ProductID
WHERE pi.PurchaseID = 1
GROUP BY pi.PurchaseID;
```

---

## Design Decisions

| Decision | Reason |
|---|---|
| No `Users` table | The application does not require accounts; customers are guest shoppers |
| `PostCode` as `VARCHAR` | Preserves leading zeros in Australian postcodes (e.g., NT: 0800) |
| `Phone` as `VARCHAR(15)` | Accommodates international format with spaces and area codes |
| `DECIMAL(10,2)` for Price | Exact monetary arithmetic; avoids floating-point imprecision |
| Composite PK on `PurchaseItem` | Prevents duplicate product entries in the same order |
| `Image` stores filename only | Avoids storing binary blobs in MySQL; images are served as static files |
| `DROP TABLE IF EXISTS` in create.sql | Allows the schema to be re-run cleanly during development |
