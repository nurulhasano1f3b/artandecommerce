# Art & E-Commerce

An online art gallery and shopping platform built with PHP and MySQL. Customers can browse original artworks across three categories — Paintings, Sculptures, and Drawings — add them to a session-based cart, and complete a purchase via a checkout form.

## Features

- Browse all available artworks with title, description, price, and category
- View individual product detail pages with full information
- Add items to a session-based shopping cart
- View cart with per-item subtotals and cart total
- Checkout with customer details (name, address, contact)
- Order confirmation with a generated Purchase ID

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP (no framework) |
| Database | MySQL via PDO |
| Frontend | HTML, CSS |
| Session State | PHP `$_SESSION` |
| Pattern | MVC (Models / Controllers / Views) |

## Project Structure

```
artandecommerce/
├── assets/
│   └── css/
│       └── styles.css          # Global stylesheet
├── config/
│   └── database.php            # PDO database connection class
├── controllers/
│   ├── ArtworkController.php   # Fetches product data
│   └── CartController.php      # Manages cart session state
├── database/
│   ├── create.sql              # Table schema definitions
│   ├── load.sql                # Sample/seed data
│   └── test.sql                # Ad-hoc test queries
├── models/
│   ├── Artwork.php             # Product queries
│   ├── Customer.php            # Customer creation
│   ├── Purchase.php            # Purchase record creation
│   └── PurchaseItem.php        # Purchase line item creation
├── public/
│   ├── addToCart.php           # POST handler — adds item to cart
│   ├── processCheckout.php     # POST handler — creates order
│   ├── removeFromCart.php      # (not yet implemented)
│   └── index.php               # Entry point placeholder
├── views/
│   ├── home.php                # Landing page
│   ├── products.php            # Product listing
│   ├── productDetails.php      # Single product detail
│   ├── cart.php                # Cart summary
│   └── checkout.php            # Checkout form
└── docs/                       # Project documentation
```

## Quick Start

1. Import the database schema and seed data:
   ```sql
   SOURCE database/create.sql;
   SOURCE database/load.sql;
   ```
2. Place the project folder under your web server's document root.
3. Open `views/home.php` in your browser.

See [Setup Guide](SetupGuide.md) for full instructions.

## Documentation

| Document | Description |
|---|---|
| [Architecture](Architecture.md) | MVC design, request flow, data flow |
| [Setup Guide](SetupGuide.md) | Local development environment setup |
| [Deployment Guide](DeploymentGuide.md) | Production server deployment |
| [Database Documentation](DatabaseDocumentation.md) | Schema, relationships, ER diagram |

## Known Limitations

- No user authentication or accounts
- No payment gateway integration (checkout collects info only)
- Cart item removal not yet implemented
- No admin panel for managing products
- No input validation beyond HTML `required` attributes
- No CSRF protection on forms
- Database credentials are hardcoded in `config/database.php`
