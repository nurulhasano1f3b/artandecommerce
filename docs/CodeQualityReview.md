# Code Quality Review

**Project:** Art & E-Commerce  
**Review date:** 2026-06-04  
**Scope:** All PHP source files, SQL schema, assets

---

## Priority Legend

| Priority | Meaning |
|---|---|
| P1 | Breaks functionality or causes a runtime crash |
| P2 | Architectural flaw that cascades across multiple files |
| P3 | Dead or unreachable code cluttering the repo |
| P4 | Naming and convention inconsistencies |
| P5 | Missing structural pieces (routing, assets, autoloading) |

---

## Summary

| Priority | Count |
|---|---|
| P1 — Functional breaks | 3 |
| P2 — Architectural | 5 |
| P3 — Dead / unused code | 7 |
| P4 — Naming conventions | 5 |
| P5 — Structural gaps | 4 |
| **Total** | **24** |

---

## P1 — Functional Breaks

These issues cause crashes or completely wrong behaviour at runtime.

---

### P1-01 — `getRecentArtwork()` is called but never defined

**File:** `controllers/ArtworkController.php:29` → `models/Artwork.php`

```php
// ArtworkController.php
public function recent() {
    $artwork = new Artwork();
    $result  = $artwork->getRecentArtwork(); // ← method does not exist
    return $result;
}
```

`Artwork.php` only defines `getAllArtworks()` and `getArtworkByID()`. Calling `recent()` on the home page triggers a fatal `Call to undefined method` error. The home page is currently broken.

**Fix — add the missing method to `models/Artwork.php`:**

```php
public function getRecentArtwork() {
    $query = "SELECT * FROM " . $this->table . " ORDER BY ProductID DESC LIMIT 1";
    $stmt  = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt;
}
```

---

### P1-02 — Debug output is active; working code is commented out

**File:** `public/addToCart.php:22–31`

```php
/*
header("Location: ../views/productDetails.php?id=$productId");
exit();
*/

echo "<pre>";
print_r($_SESSION);   // ← this runs instead of the redirect
echo "</pre>";
```

The Post-Redirect-Get pattern (the correct implementation) is commented out. The debug dump is the live code. After every add-to-cart the user sees a raw session dump instead of returning to the product page. The active and dead code are swapped.

**Fix — swap them:**

```php
header("Location: ../views/productDetails.php?id=" . (int)$productId);
exit();
```

Delete lines 28–31 entirely.

---

### P1-03 — No null check after `fetch()` — crashes on invalid product ID

**Files:** `views/productDetails.php:13`, `views/cart.php:39`

```php
// productDetails.php
$row = $product->fetch(PDO::FETCH_ASSOC);
// $row is now false if the product doesn't exist

echo $row['Title'];  // PHP warning: Trying to access array offset on false
```

If `?id=999` references a non-existent product, `fetch()` returns `false`. Every subsequent `$row['...']` access emits a PHP warning and may render a blank or broken page. In cart.php the same pattern runs inside a loop for every cart item — a single invalid product ID crashes the whole cart.

**Fix:**

```php
$row = $product->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    http_response_code(404);
    exit("Product not found.");
}
```

---

## P2 — Architectural Issues

These issues are not crashes, but their design forces the same mistake to be made in every new file.

---

### P2-01 — Identical constructor duplicated across all four models

Every model has this exact constructor:

```php
// Artwork.php:11–14
public function __construct() {
    $database = new Database();
    $this->conn = $database->connect();
}
```

The identical block appears in:

| File | Lines |
|---|---|
| `models/Artwork.php` | 11–14 |
| `models/Customer.php` | 13–17 |
| `models/Purchase.php` | 13–16 |
| `models/PurchaseItem.php` | 12–15 |

Any change to how the database is obtained (e.g., adding error handling, switching to a singleton) must be made in four places. This is the most impactful refactor available.

**Fix — introduce a `BaseModel` parent class:**

```php
// models/BaseModel.php
abstract class BaseModel {
    protected $conn;

    public function __construct() {
        $this->conn = (new Database())->connect();
    }
}

// models/Artwork.php
class Artwork extends BaseModel {
    private $table = "Products";
    // constructor removed — inherited
}
```

---

### P2-02 — New database connection opened on every controller method call

**File:** `controllers/ArtworkController.php:8–35`

```php
public function index() {
    $artwork = new Artwork(); // opens DB connection #1
    return $artwork->getAllArtworks();
}

public function show($id) {
    $artwork = new Artwork(); // opens DB connection #2
    return $artwork->getArtworkByID($id);
}

public function recent() {
    $artwork = new Artwork(); // opens DB connection #3
    return $artwork->getRecentArtwork();
}
```

Each method creates a new `Artwork` instance, which creates a new `Database` instance, which opens a new PDO connection. In `views/cart.php`, `show()` is called inside a loop — one new connection per cart item. In `public/processCheckout.php`, three different models are instantiated, opening three separate connections.

**Fix — store the model as an instance property:**

```php
class ArtworkController {
    private $artwork;

    public function __construct() {
        $this->artwork = new Artwork();
    }

    public function index()    { return $this->artwork->getAllArtworks(); }
    public function show($id)  { return $this->artwork->getArtworkByID($id); }
    public function recent()   { return $this->artwork->getRecentArtwork(); }
}
```

---

### P2-03 — Models return `PDOStatement` objects directly to views

**Files:** `models/Artwork.php:27,41`, all views

```php
// Artwork.php — returns raw statement
public function getAllArtworks() {
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt;  // ← caller gets PDOStatement
}

// views/products.php — view calls PDO methods directly
while ($row = $products->fetch(PDO::FETCH_ASSOC)):
```

The view must know that the return value is a `PDOStatement` and must call `.fetch()` on it. This leaks the data access technology (PDO) into the presentation layer. Swapping PDO for any other data source would require changing every view.

**Fix — return arrays from models:**

```php
public function getAllArtworks(): array {
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

Views become simple `foreach` loops with no PDO knowledge:

```php
foreach ($products as $row): ...
```

---

### P2-04 — Business logic (price totals) lives in the view

**File:** `views/cart.php:46–57`

```php
// Inside a view template:
$subtotal = $row['Price'] * $quantity;
echo "Subtotal: $" . number_format($subtotal, 2);
$total += $subtotal;
// ...
echo "<h3>Cart Total: $" . number_format($total, 2) . "</h3>";
```

Price calculation is business logic — it belongs in a controller or a dedicated cart summary method, not in the HTML template. If tax or discount logic is added later, it gets embedded in a view loop, making it impossible to reuse or test independently.

**Fix — move the calculation into `CartController`:**

```php
// CartController.php
public function getCartSummary(array $cartItems, array $products): array {
    $lines = [];
    $total = 0;
    foreach ($cartItems as $productId => $quantity) {
        $price    = $products[$productId]['Price'];
        $subtotal = $price * $quantity;
        $total   += $subtotal;
        $lines[]  = ['product' => $products[$productId], 'quantity' => $quantity, 'subtotal' => $subtotal];
    }
    return ['lines' => $lines, 'total' => $total];
}
```

The view then only handles rendering.

---

### P2-05 — `processCheckout.php` does everything in a single script

**File:** `public/processCheckout.php`

This 85-line script handles five distinct responsibilities with no separation:

| Lines | Responsibility |
|---|---|
| 5–14 | Load models |
| 16–31 | Read and (not) validate POST data |
| 34–46 | Create customer record |
| 52–59 | Create purchase record |
| 62–71 | Loop cart and create purchase items |
| 76–84 | Clear session and render confirmation |

There is no controller method, no service layer, and no transaction. Every concern is interleaved. Adding order confirmation emails, inventory checks, or payment processing would make this file unmanageable.

**Fix — introduce an `OrderController` with a `placeOrder()` method that wraps the transaction, and reduce the handler to:**

```php
session_start();
$controller = new OrderController();
$purchaseId = $controller->placeOrder($_POST, $_SESSION['cart']);
unset($_SESSION['cart']);
// render confirmation
```

---

## P3 — Dead and Unused Code

These files and blocks do nothing and should be deleted.

---

### P3-01 — `public/removeFromCart.php` — empty file

The file exists with a single blank line. Nothing in the codebase links to it. The cart has no remove functionality. Either implement it or delete the file; an empty stub achieves nothing.

---

### P3-02 — `public/index.php` — empty file

Described as an "entry point placeholder" but contains only a blank line. With no routing layer, this file is never loaded. Delete it or convert it into the front controller the application needs.

---

### P3-03 — `database/test.sql` — empty file

One blank line. No test queries exist. Delete it.

---

### P3-04 — `assets/css/styles.css` — empty and never linked

The file is one blank line. No view file contains a `<link rel="stylesheet">` tag pointing to it. The entire application renders as plain, unstyled HTML. The asset directory has no effect.

---

### P3-05 — Commented-out invalid SQL in `database/load.sql:1–4`

```sql
/*
INSERT INTO Customers (Email,FirstName,LastName,Title,Address,City,State,Country,PostCode,Phone,Admin)
VALUES ("ex123@gmail.com", "John", "Doe", "Mr.", "24 Faker Avenue", "Darwin", "NT", "Australia", 0800, 0412345678, 0)
*/
```

This references an `Admin` column that no longer exists in the schema, passes `0800` as an unquoted integer (drops the leading zero), and passes `0412345678` without quotes (same issue). It is invalid SQL that cannot run. The comment only adds noise — delete it.

---

### P3-06 — All five `public/test*.php` files — development artifacts

`testConnection.php`, `testCustomer.php`, `testPurchase.php`, `testPurchaseItem.php` (and `removeFromCart.php` above) are development-only scripts committed to the main codebase. Three of them **write to the database** on every request (documented in security audit C-02). Delete all of them.

---

### P3-07 — `ArtworkController::recent()` is unreachable dead code (until P1-01 is fixed)

Because `getRecentArtwork()` does not exist, calling `recent()` always crashes. Until P1-01 is resolved, the entire method body is unreachable. Once fixed it becomes live; document this together with P1-01.

---

## P4 — Naming Conventions

---

### P4-01 — `Artwork` model maps to the `Products` table — semantic mismatch

```php
class Artwork {
    private $table = "Products";
```

The model is called `Artwork`, the controller is `ArtworkController`, but the database table is `Products`. A developer reading `ArtworkController::index()` has no way to guess it queries `Products`. Either:

- Rename the class to `Product` / `ProductController` (matches the table), or
- Rename the table to `Artworks` (matches the domain language)

Pick one and be consistent across controller, model, and table.

---

### P4-02 — `getArtworkByID` — inconsistent acronym capitalisation

```php
public function getArtworkByID($id)   // ← "ID" all-caps
public function createCustomer(...)   // ← no acronym issue
public function createPurchase(...)   // ← no acronym issue
```

PHP convention (PSR) uses camelCase: `getArtworkById`. `ID` in all-caps is a Java style. Pick one convention and apply it everywhere. `getAllArtworks` / `getArtworkById` / `getRecentArtwork` would be consistent.

---

### P4-03 — `ArtworkController::recent()` — ambiguous method name

```php
public function recent() { ... }
```

`recent()` gives no indication of what it returns (a product? an array? a date?). `getMostRecentArtwork()` or `getLatestProduct()` communicates intent without requiring the caller to read the implementation.

---

### P4-04 — `Customer::createCustomer()` takes 10 positional parameters

```php
public function createCustomer(
    $email, $firstName, $lastName, $title,
    $address, $city, $state, $country, $postcode, $phone
)
```

Ten positional parameters are impossible to call correctly without counting. The caller in `processCheckout.php` must get the order exactly right. A missed or swapped argument silently populates the wrong column.

**Fix — accept an associative array or a typed DTO:**

```php
public function createCustomer(array $data): int {
    // $data['email'], $data['firstName'], etc.
}

// Caller:
$customerId = $customerModel->createCustomer([
    'email'     => $email,
    'firstName' => $firstName,
    // ...
]);
```

---

### P4-05 — Generic `$row` variable used in every view

```php
// products.php, productDetails.php, home.php, cart.php — all use:
$row = $product->fetch(PDO::FETCH_ASSOC);
echo $row['Title'];
```

`$row` carries no semantic meaning. `$product`, `$artwork`, or `$item` would make the template readable without needing to trace back to where the variable was assigned. In `cart.php` especially, both `$product` (a PDOStatement) and `$row` (the fetched array) are present in the same scope, which is confusing.

---

## P5 — Structural Gaps

---

### P5-01 — No routing layer — URL equals file path

Every page is a directly-accessible PHP file:

```
http://localhost/artandecommerce/views/products.php
http://localhost/artandecommerce/views/productDetails.php?id=1
http://localhost/artandecommerce/public/processCheckout.php
```

This means:
- Changing a URL requires renaming a file (breaking bookmarks and links)
- Any file in `views/` or `public/` is reachable directly with no access control
- POST handlers (`addToCart.php`, `processCheckout.php`) are accessible via GET with no parameters, causing undefined variable errors

A front controller (`public/index.php`) with a route map would centralise URL handling.

---

### P5-02 — No autoloading — relative `require_once` paths everywhere

```php
require_once "../config/database.php";   // in every model
require_once "../models/Artwork.php";    // in every controller
require_once "../controllers/ArtworkController.php"; // in every view
```

Every file path is relative. Moving any file breaks every file that includes it. A PSR-4 autoloader (via Composer or a manual `spl_autoload_register`) would eliminate all manual requires and make the include graph automatic.

---

### P5-03 — CSS file is empty and never referenced

`assets/css/styles.css` is empty (one blank line). No view includes a `<link>` to it. The application has no styling at all. At minimum, add the link tag to each view:

```html
<link rel="stylesheet" href="../assets/css/styles.css">
```

Then add content to the stylesheet. An unstyled application is hard to use and gives a poor impression during demonstrations.

---

### P5-04 — No shared HTML layout — `<head>` duplicated in every view

Each view file is a complete standalone HTML document:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>...</title>
</head>
<body>
```

The boilerplate is repeated across `home.php`, `products.php`, `productDetails.php`, `cart.php`, and `checkout.php`. Adding a meta tag, favicon, or stylesheet link requires editing five files.

**Fix — extract a layout template:**

```php
// views/layout/header.php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <title><?php echo $pageTitle ?? 'Art Site'; ?></title>
</head>
<body>

// views/layout/footer.php
</body>
</html>

// views/products.php
<?php $pageTitle = "Products"; require 'layout/header.php'; ?>
... page content ...
<?php require 'layout/footer.php'; ?>
```

---

## Ranked Issue List

| Rank | ID | Issue | Files affected |
|---|---|---|---|
| 1 | P1-01 | `getRecentArtwork()` missing — home page crashes | `Artwork.php`, `ArtworkController.php` |
| 2 | P1-02 | Debug dump active; redirect commented out | `addToCart.php` |
| 3 | P1-03 | No null check on `fetch()` — crashes on bad product ID | `productDetails.php`, `cart.php` |
| 4 | P2-01 | Identical constructor in all 4 models | All models |
| 5 | P2-02 | New DB connection on every controller method call | `ArtworkController.php`, all models |
| 6 | P2-03 | Models return `PDOStatement` to views | All models, all views |
| 7 | P2-05 | `processCheckout.php` does everything in one script | `processCheckout.php` |
| 8 | P2-04 | Price calculation in view | `cart.php` |
| 9 | P3-06 | Five test files pollute `public/` and write to DB | `public/test*.php` |
| 10 | P4-01 | `Artwork` model / `Products` table naming mismatch | All models, controllers |
| 11 | P4-04 | 10 positional parameters on `createCustomer()` | `Customer.php`, `processCheckout.php` |
| 12 | P4-02 | `getArtworkByID` — inconsistent acronym style | `Artwork.php` |
| 13 | P4-03 | `recent()` — ambiguous method name | `ArtworkController.php` |
| 14 | P4-05 | `$row` used as variable name in every view | All views |
| 15 | P3-01 | `removeFromCart.php` — empty stub | `public/removeFromCart.php` |
| 16 | P3-02 | `index.php` — empty stub | `public/index.php` |
| 17 | P3-03 | `test.sql` — empty file | `database/test.sql` |
| 18 | P3-04 | `styles.css` — empty, never linked | `assets/css/styles.css` |
| 19 | P3-05 | Invalid commented-out SQL in `load.sql` | `database/load.sql` |
| 20 | P5-01 | No routing layer | Project-wide |
| 21 | P5-02 | No autoloading — relative paths everywhere | All `require_once` calls |
| 22 | P5-03 | CSS never applied to any page | All views |
| 23 | P5-04 | HTML boilerplate duplicated in every view | All views |
| 24 | P3-07 | `recent()` body unreachable until P1-01 fixed | `ArtworkController.php` |
