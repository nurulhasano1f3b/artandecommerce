# Database Analysis

Full inspection of `database/create.sql`, all models, controllers, views, and public handlers.

---

## 1. Entity-Relationship Diagram

```
┌─────────────────────────────────┐
│            Customers            │
├─────────────────────────────────┤
│ PK  CustomerID   INT AI         │
│     Email        VARCHAR(255)   │
│     FirstName    VARCHAR(50)    │
│     LastName     VARCHAR(50)    │
│     Title        ENUM           │
│     Address      VARCHAR(75)    │
│     City         VARCHAR(50)    │
│     State        VARCHAR(50)    │
│     Country      VARCHAR(50)    │
│     PostCode     VARCHAR(10)    │
│     Phone        VARCHAR(15)    │
└────────────────┬────────────────┘
                 │ 1
                 │
                 │ (one customer → many purchases)
                 │
                 │ M
┌────────────────▼────────────────┐
│            Purchases            │
├─────────────────────────────────┤
│ PK  PurchaseID   INT AI         │
│ FK  CustomerID   INT  ──────────┼──► Customers(CustomerID)
│     PurchaseDate DATETIME       │
└────────────────┬────────────────┘
                 │ 1
                 │
                 │ (one purchase → many line items)
                 │
                 │ M
┌────────────────▼────────────────┐
│           PurchaseItem          │   M ┌────────────────────────────────┐
├─────────────────────────────────┤◄────┤            Products            │
│ PK,FK  PurchaseID  INT ─────────┼────►│Purchases(PurchaseID)           │
│ PK,FK  ProductID   INT ─────────┼────►├────────────────────────────────┤
│        Quantity    INT(5)       │     │ PK  ProductID   INT AI         │
└─────────────────────────────────┘     │     Title       VARCHAR(100)   │
                                        │     Description TEXT           │
                                        │     Price       DECIMAL(10,2)  │
                                        │     Category    ENUM           │
                                        │     Image       VARCHAR(255)   │
                                        └────────────────────────────────┘
```

**Cardinalities:**

| Relationship | Type | Note |
|---|---|---|
| Customers → Purchases | One-to-Many | One customer has zero or more purchases |
| Purchases → PurchaseItem | One-to-Many | One purchase has one or more line items |
| Products → PurchaseItem | One-to-Many | One product can appear in many orders |
| Purchases ↔ Products (via PurchaseItem) | Many-to-Many | `PurchaseItem` is the bridge table, adding `Quantity` |

---

## 2. Table Descriptions

### `Customers`

Holds guest customer details captured at checkout. There is no login system; each checkout creates a new row, even for returning buyers.

| Column | Type | Notes |
|---|---|---|
| `CustomerID` | `INT AUTO_INCREMENT` | Surrogate primary key |
| `Email` | `VARCHAR(255)` | No UNIQUE constraint — duplicates allowed |
| `FirstName` | `VARCHAR(50)` | — |
| `LastName` | `VARCHAR(50)` | — |
| `Title` | `ENUM('Mr.','Mrs.','Mx.','Ms.')` | Nullable; not enforced server-side |
| `Address` | `VARCHAR(75)` | Street line only |
| `City` | `VARCHAR(50)` | — |
| `State` | `VARCHAR(50)` | Free text; not validated against a reference list |
| `Country` | `VARCHAR(50)` | Free text |
| `PostCode` | `VARCHAR(10)` | VARCHAR deliberately — preserves leading zeros (e.g. NT `0800`) |
| `Phone` | `VARCHAR(15)` | VARCHAR — allows spaces and country codes |

---

### `Products`

The artwork catalogue. All items on sale live here.

| Column | Type | Notes |
|---|---|---|
| `ProductID` | `INT AUTO_INCREMENT` | Surrogate primary key |
| `Title` | `VARCHAR(100)` | Displayed as the product name |
| `Description` | `TEXT` | Long-form copy |
| `Price` | `DECIMAL(10,2)` | Exact monetary storage; avoids floating-point errors |
| `Category` | `ENUM('Painting','Sculpture','Drawing')` | Closed set; adding a new category requires `ALTER TABLE` |
| `Image` | `VARCHAR(255)` | Stores filename only (e.g., `sunset.jpg`); defaults to `placeholder.jpg` |

---

### `Purchases`

One row per completed order. Links a customer to a timestamp; line items are in `PurchaseItem`.

| Column | Type | Notes |
|---|---|---|
| `PurchaseID` | `INT AUTO_INCREMENT` | Surrogate primary key |
| `CustomerID` | `INT` | FK → `Customers(CustomerID)`; NOT NULL |
| `PurchaseDate` | `DATETIME` | Set with `NOW()` at insert time in `Purchase::createPurchase()` |

---

### `PurchaseItem`

Bridge/junction table. Each row is one product line within one purchase, with a quantity.

| Column | Type | Notes |
|---|---|---|
| `PurchaseID` | `INT` | Part of composite PK; FK → `Purchases(PurchaseID)` |
| `ProductID` | `INT` | Part of composite PK; FK → `Products(ProductID)` |
| `Quantity` | `INT(5)` | Display width `(5)` is deprecated in MySQL 8.0+ and has no effect on storage |

The composite primary key `(PurchaseID, ProductID)` prevents the same product from appearing twice in the same order.

---

## 3. Foreign Key Relationships

| Constraint | Table | Column | References | On Delete | On Update |
|---|---|---|---|---|---|
| `fk_Customer` | `Purchases` | `CustomerID` | `Customers(CustomerID)` | RESTRICT (default) | RESTRICT (default) |
| `fk_Purchase` | `PurchaseItem` | `PurchaseID` | `Purchases(PurchaseID)` | RESTRICT (default) | RESTRICT (default) |
| `fk_Product` | `PurchaseItem` | `ProductID` | `Products(ProductID)` | RESTRICT (default) | RESTRICT (default) |

**RESTRICT means:** attempting to delete a `Customer` who has `Purchases`, or a `Product` that appears in a `PurchaseItem`, will throw an error. This is safe for an e-commerce store but no explicit action is documented in the schema.

**InnoDB auto-indexes:** MySQL InnoDB automatically creates a supporting index on a foreign key column if no usable index exists. This means:
- `Purchases.CustomerID` — gets an auto-index from `fk_Customer`
- `PurchaseItem.ProductID` — gets an auto-index from `fk_Product` (the composite PK only covers leading key `PurchaseID`)

These indexes exist but are undocumented and invisible without running `SHOW INDEX FROM table`.

---

## 4. Missing Indexes

### Analysis of all queries executed in the codebase

| Query | Location | Columns filtered / sorted |
|---|---|---|
| `SELECT * FROM Products` | `Artwork::getAllArtworks()` | Full scan — no filter |
| `SELECT * FROM Products WHERE ProductID = ?` | `Artwork::getArtworkByID()` | `ProductID` (PK ✓) |
| `SELECT * FROM Products ORDER BY ProductID DESC LIMIT 1` | `Artwork::getRecentArtwork()` *(missing — see bugs)* | `ProductID` (PK ✓) |
| `INSERT INTO Customers (...)` | `Customer::createCustomer()` | — |
| `INSERT INTO Purchases (PurchaseDate, CustomerID)` | `Purchase::createPurchase()` | — |
| `INSERT INTO PurchaseItem (PurchaseID, ProductID, Quantity)` | `PurchaseItem::createPurchaseItem()` | — |

The current queries only look up by primary key or do full scans, so no query is slow right now on a small dataset. However, the following indexes are missing and will matter as data grows or features are added:

---

### Missing Index 1 — `Customers.Email`

**Why it matters:** Email is the natural identifier for a customer. Any future feature — "look up order history by email", "check if this email has ordered before" — will do a full table scan without this.

```sql
ALTER TABLE Customers ADD INDEX idx_customers_email (Email);
```

If email should be unique per customer (preventing duplicate records for the same buyer):

```sql
ALTER TABLE Customers ADD UNIQUE INDEX uniq_customers_email (Email);
```

---

### Missing Index 2 — `Products.Category`

**Why it matters:** The `Category` enum is the primary way artworks are grouped. A "filter by category" feature (the most obvious next step for this store) does a full scan of `Products` without this.

```sql
ALTER TABLE Products ADD INDEX idx_products_category (Category);
```

---

### Missing Index 3 — `Products.Price`

**Why it matters:** Sorting by price or filtering by price range (`WHERE Price BETWEEN ? AND ?`) are common e-commerce operations. No index exists to support these.

```sql
ALTER TABLE Products ADD INDEX idx_products_price (Price);
```

---

### Missing Index 4 — `Purchases.PurchaseDate`

**Why it matters:** Any reporting ("orders placed today", "revenue this week") or admin panel filtering by date does a full scan of `Purchases` without this.

```sql
ALTER TABLE Purchases ADD INDEX idx_purchases_date (PurchaseDate);
```

---

### Missing Index 5 — `Products.Title` (FULLTEXT)

**Why it matters:** Search is almost always added to e-commerce stores. A `WHERE Title LIKE '%keyword%'` query cannot use a B-tree index. A FULLTEXT index supports `MATCH ... AGAINST` searches efficiently.

```sql
ALTER TABLE Products ADD FULLTEXT INDEX ft_products_title_description (Title, Description);
```

---

### Summary of all missing indexes

```sql
-- Run these after the schema is live:

ALTER TABLE Customers
    ADD UNIQUE INDEX uniq_customers_email (Email);

ALTER TABLE Products
    ADD INDEX idx_products_category (Category),
    ADD INDEX idx_products_price (Price),
    ADD FULLTEXT INDEX ft_products_search (Title, Description);

ALTER TABLE Purchases
    ADD INDEX idx_purchases_date (PurchaseDate);
```

---

## 5. Optimization Suggestions

### Bug: `getRecentArtwork()` method does not exist

**Severity: Critical — causes a fatal PHP error on the home page.**

`ArtworkController::recent()` (line 28) calls `$artwork->getRecentArtwork()`, but `Artwork.php` only defines `getAllArtworks()` and `getArtworkByID()`. This method is missing.

**Fix — add to `models/Artwork.php`:**

```php
public function getRecentArtwork() {
    $query = "SELECT * FROM " . $this->table . " ORDER BY ProductID DESC LIMIT 1";
    $stmt  = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt;
}
```

---

### Bug: No transaction wrapping in `processCheckout.php`

**Severity: High — causes orphaned data on failure.**

`processCheckout.php` executes three separate INSERT operations (Customer → Purchase → PurchaseItems) with no transaction. If any step fails midway (e.g., a network hiccup after the Customer insert), the database is left in an inconsistent state: a customer record exists with no purchase, or a purchase exists with no items.

**Fix — wrap in a transaction:**

```php
$conn = (new Database())->connect();
try {
    $conn->beginTransaction();

    $customerId  = $customerModel->createCustomer(...);
    $purchaseId  = $purchaseModel->createPurchase($customerId);

    foreach ($_SESSION['cart'] as $productId => $quantity) {
        $purchaseItemModel->createPurchaseItem($purchaseId, $productId, $quantity);
    }

    $conn->commit();
    unset($_SESSION['cart']);

} catch (Exception $e) {
    $conn->rollBack();
    echo "Order failed. Please try again.";
}
```

This requires models to accept an injected connection rather than creating their own — see the connection-per-model issue below.

---

### Performance: N+1 query problem in `cart.php`

**Severity: Medium — scales linearly with cart size.**

`views/cart.php` (lines 38–39) calls `$controller->show($productId)` inside a `foreach` loop. For a cart with N items, this fires N separate `SELECT` queries:

```
Cart has 5 items → 5 round-trips to MySQL
Cart has 20 items → 20 round-trips to MySQL
```

**Fix — add a batch fetch method to `Artwork.php`:**

```php
public function getArtworksByIDs(array $ids) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $query = "SELECT * FROM " . $this->table . " WHERE ProductID IN ($placeholders)";
    $stmt  = $this->conn->prepare($query);
    $stmt->execute($ids);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

Then in `cart.php`:

```php
$ids      = array_keys($_SESSION['cart']);
$products = $controller->getBatch($ids); // 1 query total
$productMap = array_column($products, null, 'ProductID');
```

---

### Design: One database connection per model instance

**Severity: Medium — wastes connections on every page load.**

Each model (`Artwork`, `Customer`, `Purchase`, `PurchaseItem`) calls `new Database()` and `connect()` in its constructor. `processCheckout.php` instantiates all three models, creating three separate PDO connections to MySQL.

**Fix — inject a shared connection:**

```php
// In each model constructor, accept $conn instead of creating one:
public function __construct(PDO $conn) {
    $this->conn = $conn;
}

// Caller creates one connection and shares it:
$db   = new Database();
$conn = $db->connect();

$customerModel     = new Customer($conn);
$purchaseModel     = new Purchase($conn);
$purchaseItemModel = new PurchaseItem($conn);
```

---

### Design: Duplicate customer records on every checkout

**Severity: Medium — data quality issue.**

Every checkout creates a new `Customers` row regardless of whether the email already exists. A customer who places 10 orders generates 10 separate records. This makes order history lookup by customer impossible and inflates the table.

**Fix option A — check before insert:**

```php
$query = "SELECT CustomerID FROM Customers WHERE Email = ? LIMIT 1";
// If found, reuse that CustomerID; if not, INSERT.
```

**Fix option B — use `INSERT ... ON DUPLICATE KEY UPDATE` with a UNIQUE index on Email:**

```sql
INSERT INTO Customers (Email, FirstName, ...) VALUES (?, ?, ...)
ON DUPLICATE KEY UPDATE FirstName = VALUES(FirstName), ...;
```

---

### Schema: `INT(5)` display width on `PurchaseItem.Quantity`

**Severity: Low — deprecated syntax, no functional impact.**

`Quantity INT(5)` uses a display width, which MySQL 8.0 deprecated and MySQL 8.0.17 began warning about. It has no effect on storage range — `INT` always stores –2,147,483,648 to 2,147,483,647 regardless of display width.

**Fix:**

```sql
ALTER TABLE PurchaseItem MODIFY COLUMN Quantity INT NOT NULL;
```

Or, since quantities should never be negative, use `UNSIGNED`:

```sql
ALTER TABLE PurchaseItem MODIFY COLUMN Quantity INT UNSIGNED NOT NULL;
```

---

### Schema: No `ON DELETE` / `ON UPDATE` actions on foreign keys

**Severity: Low — operational gap, not a bug.**

All three FK constraints use default `RESTRICT`. This is safe but brittle: deleting any `Customer` or `Product` will throw a database error rather than handling it gracefully. The expected business rule should be encoded in the schema.

**Recommended:**

```sql
-- If a customer is deleted, keep their purchase history (block the delete):
CONSTRAINT fk_Customer FOREIGN KEY (CustomerID)
    REFERENCES Customers(CustomerID)
    ON DELETE RESTRICT ON UPDATE CASCADE;

-- If a product is deleted, block if it appears in any order:
CONSTRAINT fk_Product FOREIGN KEY (ProductID)
    REFERENCES Products(ProductID)
    ON DELETE RESTRICT ON UPDATE CASCADE;
```

`ON UPDATE CASCADE` means if a `CustomerID` or `ProductID` changes (unlikely with auto-increment, but possible in migrations), child rows update automatically.

---

### Schema: No explicit `ENGINE=InnoDB` declaration

**Severity: Low — works fine on MySQL 8.0 defaults, fragile on older installs.**

MySQL 8.0 defaults to InnoDB, which supports transactions and foreign keys. MySQL 5.5 and 5.6 defaulted to MyISAM on some distributions, which silently ignores FK constraints.

**Fix — make it explicit:**

```sql
CREATE TABLE Customers ( ... ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE Products  ( ... ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE Purchases ( ... ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE PurchaseItem ( ... ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

### Security: No server-side input validation before INSERT

**Severity: High.**

`processCheckout.php` reads `$_POST` values and passes them directly to prepared statements. Prepared statements protect against SQL injection, but there is no validation of:

- Email format (could store `"not-an-email"`)
- Phone format (could store arbitrary strings)
- PostCode length (could overflow `VARCHAR(10)` from a malformed request)
- Negative or zero quantities in the cart (session could be tampered)

**Minimum fix for checkout handler:**

```php
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email address.");
}
if (empty($firstName) || strlen($firstName) > 50) {
    die("Invalid first name.");
}
foreach ($_SESSION['cart'] as $productId => $quantity) {
    if (!is_numeric($productId) || (int)$quantity < 1) {
        die("Invalid cart data.");
    }
}
```

---

## Summary Table

| # | Issue | Severity | File | Fix type |
|---|---|---|---|---|
| 1 | `getRecentArtwork()` method missing | **Critical** | `models/Artwork.php` | Add method |
| 2 | No transaction on checkout | **High** | `public/processCheckout.php` | Add BEGIN/COMMIT/ROLLBACK |
| 3 | No server-side input validation | **High** | `public/processCheckout.php` | Add validation |
| 4 | N+1 queries in cart page | Medium | `views/cart.php` | Batch fetch method |
| 5 | One DB connection per model | Medium | All models | Inject shared PDO |
| 6 | Duplicate customers per checkout | Medium | `models/Customer.php` | Upsert by email |
| 7 | Missing index on `Customers.Email` | Medium | Schema | `ADD UNIQUE INDEX` |
| 8 | Missing index on `Products.Category` | Medium | Schema | `ADD INDEX` |
| 9 | Missing index on `Products.Price` | Low | Schema | `ADD INDEX` |
| 10 | Missing index on `Purchases.PurchaseDate` | Low | Schema | `ADD INDEX` |
| 11 | Missing FULLTEXT index on `Products` | Low | Schema | `ADD FULLTEXT INDEX` |
| 12 | `INT(5)` deprecated on `PurchaseItem.Quantity` | Low | Schema | `MODIFY COLUMN` |
| 13 | No `ON DELETE`/`ON UPDATE` on FK constraints | Low | Schema | Explicit FK actions |
| 14 | No explicit `ENGINE=InnoDB` | Low | Schema | Add to CREATE TABLE |
