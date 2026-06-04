# Setup Guide

This guide walks through setting up the Art & E-Commerce application on a local development machine.

---

## Prerequisites

| Requirement | Minimum Version | Notes |
|---|---|---|
| PHP | 7.4+ | Must have PDO and PDO_MySQL extensions enabled |
| MySQL | 5.7+ (or MariaDB 10.3+) | — |
| Web Server | Apache 2.4+ or XAMPP/WAMP/MAMP | Must support PHP execution |
| phpMyAdmin | Any | Optional but helpful for database management |

> **Recommended:** Use [XAMPP](https://www.apachefriends.org/) on Windows — it bundles Apache, MySQL, and PHP in a single installer.

---

## Step 1 — Install a Local Server Stack

### Option A: XAMPP (recommended for Windows)

1. Download and install XAMPP from https://www.apachefriends.org/
2. Start the **Apache** and **MySQL** modules from the XAMPP Control Panel.
3. Verify Apache is running by visiting `http://localhost` in your browser.

### Option B: PHP built-in server (no Apache needed)

```powershell
# From the project root
php -S localhost:8000 -t views
```

Note: with the built-in server, POST handlers in `public/` must be accessed as `http://localhost:8000/../public/addToCart.php` — adjust form `action` attributes accordingly.

---

## Step 2 — Place the Project Files

1. Clone or copy the project into XAMPP's web root:
   ```powershell
   # Default XAMPP htdocs path on Windows
   C:\xampp\htdocs\artandecommerce\
   ```
2. Confirm the folder structure matches what is described in the [README](README.md).

---

## Step 3 — Create the Database

1. Open phpMyAdmin at `http://localhost/phpmyadmin`.
2. Click **New** in the left sidebar and create a database named exactly:
   ```
   ecommerce_db
   ```
   Collation: `utf8mb4_general_ci`

3. Select the `ecommerce_db` database, then click the **SQL** tab.

4. Paste and run the contents of `database/create.sql` to create all tables:
   ```sql
   USE ecommerce_db;

   CREATE TABLE Customers ( ... );
   CREATE TABLE Products ( ... );
   CREATE TABLE Purchases ( ... );
   CREATE TABLE PurchaseItem ( ... );
   ```

5. *(Optional)* Load sample data by running `database/load.sql` in the same SQL tab. This inserts:
   - 1 test customer (John Doe)
   - 3 sample products (Sunset Landscape, Ocean Sculpture, Abstract Sketch)
   - 1 sample purchase

---

## Step 4 — Configure the Database Connection

Open `config/database.php` and verify the credentials match your local MySQL setup:

```php
private $host     = "localhost";
private $db_name  = "ecommerce_db";
private $username = "root";
private $password = "";        // XAMPP default is empty
```

If your MySQL root password is set, update `$password` accordingly.

---

## Step 5 — Verify the Connection

Visit the test file in your browser:

```
http://localhost/artandecommerce/public/testConnection.php
```

If the page loads without a "Connection Error", the database is connected correctly.

---

## Step 6 — Open the Application

Navigate to the home page:

```
http://localhost/artandecommerce/views/home.php
```

From here you can:
- Browse products at `views/products.php`
- View a product detail and add it to the cart
- Review the cart at `views/cart.php`
- Complete checkout at `views/checkout.php`

---

## Troubleshooting

### "Connection Error: SQLSTATE[HY000]..."
- Confirm MySQL is running in the XAMPP Control Panel.
- Check that the database name in `config/database.php` matches exactly (`ecommerce_db`).
- Check the username and password.

### Blank page or PHP errors
- Enable PHP error display by adding this to the top of any PHP file temporarily:
  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```
- Check the Apache error log at `C:\xampp\apache\logs\error.log`.

### "Table doesn't exist"
- You may have skipped Step 3. Run `database/create.sql` in phpMyAdmin.

### Cart is empty after adding items
- PHP sessions require cookies. Make sure your browser accepts cookies for `localhost`.
- Verify `session_start()` is called before any output in the handler files.

---

## PHP Extensions Required

Confirm these are enabled in `php.ini` (remove the leading `;` to uncomment):

```ini
extension=pdo_mysql
extension=pdo
```

On XAMPP, both are enabled by default.
