# Architecture

## Overview

Art & E-Commerce follows a lightweight **MVC (Model-View-Controller)** pattern implemented in plain PHP with no framework. The application is split into four layers:

| Layer | Directory | Responsibility |
|---|---|---|
| View | `views/` | HTML/PHP templates rendered directly in the browser |
| Controller | `controllers/` | Business logic; bridges models and views |
| Model | `models/` | Database queries via PDO prepared statements |
| Handler | `public/` | POST endpoint scripts (form action targets) |

---

## Directory Map

```
artandecommerce/
├── config/database.php       # Database singleton — PDO connection
├── controllers/
│   ├── ArtworkController.php # Product listing and detail logic
│   └── CartController.php    # Session cart management
├── models/
│   ├── Artwork.php           # SELECT queries on Products table
│   ├── Customer.php          # INSERT into Customers
│   ├── Purchase.php          # INSERT into Purchases
│   └── PurchaseItem.php      # INSERT into PurchaseItem
├── views/
│   ├── home.php              # Landing page — most recent product
│   ├── products.php          # Full product catalogue
│   ├── productDetails.php    # Single product + add-to-cart form
│   ├── cart.php              # Cart contents + totals
│   └── checkout.php          # Customer details form
└── public/
    ├── addToCart.php         # POST: adds product to $_SESSION cart
    └── processCheckout.php   # POST: creates Customer, Purchase, PurchaseItems
```

---

## Request Flow

### Browsing Products

```
Browser
  └─► views/products.php
          └─► require ArtworkController.php
                  └─► require Artwork.php (model)
                          └─► require config/database.php
                                  └─► PDO → MySQL (SELECT * FROM Products)
                      ← returns $artworks array
          └─► loops $artworks → renders HTML product cards
```

### Adding to Cart

```
Browser (form POST from productDetails.php)
  └─► public/addToCart.php
          └─► session_start()
          └─► CartController::addToCart($productId)
                  └─► checks $_SESSION['cart'][$productId]
                      if exists → increments Quantity
                      if new    → creates entry {ProductID, Quantity: 1}
          ← redirects (or echoes session for debug)
```

### Checkout / Order Creation

```
Browser (form POST from views/checkout.php)
  └─► public/processCheckout.php
          ├─► Customer::createCustomer(formData)
          │       └─► INSERT INTO Customers → returns $customerId
          ├─► Purchase::createPurchase($customerId)
          │       └─► INSERT INTO Purchases (NOW(), $customerId) → returns $purchaseId
          ├─► foreach $_SESSION['cart'] as $item
          │       └─► PurchaseItem::createPurchaseItem($purchaseId, $productId, $quantity)
          │               └─► INSERT INTO PurchaseItem
          ├─► unset($_SESSION['cart'])
          └─► displays order confirmation + $purchaseId
```

---

## Data Flow Diagram

```
[Browser]
    │
    │ GET  views/home.php
    │      views/products.php
    │      views/productDetails.php
    │      views/cart.php
    │      views/checkout.php
    │
    │ POST public/addToCart.php ──────────► $_SESSION['cart']
    │
    │ POST public/processCheckout.php
    │           │
    │           ├── INSERT Customers ──────────────────────────────────────► [MySQL]
    │           ├── INSERT Purchases (FK: CustomerID) ─────────────────────► [MySQL]
    │           └── INSERT PurchaseItem × N (FK: PurchaseID, ProductID) ──► [MySQL]
    │
    └── [MySQL ecommerce_db]
            ├── Customers
            ├── Products
            ├── Purchases
            └── PurchaseItem
```

---

## Database Connection

`config/database.php` defines a `Database` class with a single `connect()` method that returns a PDO instance. Each model instantiates this class and calls `connect()` to obtain a connection. There is no connection pooling or singleton enforcement beyond PHP's request lifecycle.

```php
$db   = new Database();
$conn = $db->connect();  // returns PDO instance
```

PDO is configured with `PDO::ERRMODE_EXCEPTION` so database errors throw catchable exceptions rather than silently failing.

---

## Session-Based Cart

The cart is stored entirely in `$_SESSION['cart']`, keyed by `ProductID`:

```php
$_SESSION['cart'] = [
    1 => ['ProductID' => 1, 'Quantity' => 2],
    3 => ['ProductID' => 3, 'Quantity' => 1],
];
```

This means:
- The cart persists for the duration of the PHP session (until browser close or session expiry).
- No database row is written until checkout is completed.
- Cart state is server-side only; no JavaScript or localStorage is involved.

---

## Security Notes

| Area | Current State |
|---|---|
| SQL injection | Protected — all queries use PDO prepared statements |
| XSS | Not addressed — output is not HTML-escaped with `htmlspecialchars` |
| CSRF | No tokens on forms |
| Credentials | Hardcoded in `config/database.php` |
| Input validation | HTML `required` only; no server-side validation |
| Authentication | None — no user accounts or sessions beyond cart |
