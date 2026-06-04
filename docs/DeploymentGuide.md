# Deployment Guide

This guide covers deploying the Art & E-Commerce application to a production Linux server running Apache and MySQL.

---

## Prerequisites

| Requirement | Version |
|---|---|
| Linux server (Ubuntu 22.04 LTS recommended) | — |
| Apache | 2.4+ |
| PHP | 8.1+ |
| MySQL | 8.0+ |
| SSH access | — |
| Domain name (optional) | — |

---

## Step 1 — Provision the Server

### Install the LAMP stack

```bash
sudo apt update && sudo apt upgrade -y

# Apache
sudo apt install apache2 -y

# MySQL
sudo apt install mysql-server -y
sudo mysql_secure_installation   # set root password, remove test DBs

# PHP + required extensions
sudo apt install php libapache2-mod-php php-mysql php-pdo -y

# Verify versions
php -v
mysql --version
apache2 -v
```

---

## Step 2 — Deploy Application Files

### Option A — Git (recommended)

```bash
cd /var/www/html
sudo git clone https://github.com/<your-username>/artandecommerce.git
sudo chown -R www-data:www-data artandecommerce/
sudo chmod -R 755 artandecommerce/
```

### Option B — SCP / SFTP

```bash
# From your local machine
scp -r ./artandecommerce user@your-server-ip:/var/www/html/
```

---

## Step 3 — Create the Production Database

```bash
# Log in to MySQL as root
sudo mysql -u root -p
```

```sql
CREATE DATABASE ecommerce_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Create a dedicated application user (do NOT use root in production)
CREATE USER 'artapp'@'localhost' IDENTIFIED BY 'StrongPasswordHere';
GRANT SELECT, INSERT, UPDATE, DELETE ON ecommerce_db.* TO 'artapp'@'localhost';
FLUSH PRIVILEGES;

EXIT;
```

Import the schema:

```bash
sudo mysql -u artapp -p ecommerce_db < /var/www/html/artandecommerce/database/create.sql
```

Optionally load sample data:

```bash
sudo mysql -u artapp -p ecommerce_db < /var/www/html/artandecommerce/database/load.sql
```

---

## Step 4 — Update Database Credentials

Edit `config/database.php` with the production database user:

```php
private $host     = "localhost";
private $db_name  = "ecommerce_db";
private $username = "artapp";
private $password = "StrongPasswordHere";
```

> **Security:** Never commit production credentials to version control. Consider using environment variables (see [Hardening](#hardening) below).

---

## Step 5 — Configure Apache Virtual Host

Create a virtual host configuration:

```bash
sudo nano /etc/apache2/sites-available/artandecommerce.conf
```

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/html/artandecommerce

    <Directory /var/www/html/artandecommerce>
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/artandecommerce_error.log
    CustomLog ${APACHE_LOG_DIR}/artandecommerce_access.log combined
</VirtualHost>
```

Enable the site and restart Apache:

```bash
sudo a2ensite artandecommerce.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

## Step 6 — Enable HTTPS (SSL/TLS)

Use Certbot (Let's Encrypt) for a free SSL certificate:

```bash
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com
```

Certbot will automatically modify the virtual host to redirect HTTP → HTTPS.

---

## Step 7 — Verify Deployment

1. Visit `https://yourdomain.com/views/home.php` — the home page should load.
2. Visit `https://yourdomain.com/public/testConnection.php` — should show no errors.
3. Delete or restrict `public/testConnection.php`, `public/testCustomer.php`, `public/testPurchase.php`, and `public/testPurchaseItem.php` after verifying — these expose internal information:
   ```bash
   sudo rm /var/www/html/artandecommerce/public/test*.php
   ```

---

## Hardening

### Use environment variables for credentials

Instead of hardcoding credentials in `config/database.php`, set server environment variables in the Apache virtual host:

```apache
SetEnv DB_HOST     localhost
SetEnv DB_NAME     ecommerce_db
SetEnv DB_USER     artapp
SetEnv DB_PASS     StrongPasswordHere
```

Then update `config/database.php`:

```php
private $host     = $_ENV['DB_HOST'];
private $db_name  = $_ENV['DB_NAME'];
private $username = $_ENV['DB_USER'];
private $password = $_ENV['DB_PASS'];
```

### Disable directory listing

Add to `.htaccess` in the project root:

```apache
Options -Indexes
```

### Restrict direct access to non-public directories

Add to `.htaccess`:

```apache
# Block direct access to config, models, controllers, database
<FilesMatch "\.(php|sql)$">
    <RequireAll>
        Require all denied
    </RequireAll>
</FilesMatch>
```

Or use Apache `<Directory>` directives in the virtual host to deny access to `config/`, `models/`, `controllers/`, and `database/` directories directly.

### PHP production settings

In `/etc/php/8.1/apache2/php.ini`:

```ini
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
expose_php = Off
```

---

## Maintenance

### Updating the application

```bash
cd /var/www/html/artandecommerce
sudo git pull origin main
sudo chown -R www-data:www-data .
```

### Backing up the database

```bash
mysqldump -u artapp -p ecommerce_db > backup_$(date +%Y%m%d).sql
```

### Viewing Apache logs

```bash
sudo tail -f /var/log/apache2/artandecommerce_error.log
sudo tail -f /var/log/apache2/artandecommerce_access.log
```
