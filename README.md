# EcoCart Ecommerce Demo

EcoCart is a PHP, MySQL, XAMPP, HTML, JavaScript, and DaisyUI demo system for the DDoS awareness screenplay.

## Run Locally

1. Start Apache and MySQL in XAMPP.
2. Open phpMyAdmin.
3. Create a database named `ecocart_demo`.
4. Select that database and import `database/schema.sql`.
5. Visit `http://localhost/Ecommerce/index.php`.

The storefront works in demo mode even before the database is imported. Orders are saved only after MySQL is connected.

## Pages

- `index.php` - storefront, sale countdown, products, cart
- `product.php` - product details, ratings, sortable discussions, and reactions
- `checkout.php` - customer checkout and order save
- `admin.php` - operations dashboard and safe fake terminal simulator
- `director.php` - three filming cues, discussion moderation, and customer safety controls
- `profile-setup.php` - customer identity setup with optional profile pictures

## Hosting

See `DEPLOYMENT.md` for GitHub Actions, GoogieHost FTPS, and production
database setup.

Database credentials may be supplied through `ECOCART_DB_HOST`,
`ECOCART_DB_NAME`, `ECOCART_DB_USER`, and `ECOCART_DB_PASS`. On hosting without
environment-variable support, create the ignored `includes/config.local.php`
file from `includes/config.local.example.php`.

## Safe Simulation

The admin terminal is only a visual simulation. It does not run network commands, connect to external targets, scan, flood, or attack anything.

## Product Community

Everyone can read product reviews. Registered customer accounts can post,
toggle Helpful/Love/Funny reactions, and soft-delete only their own comments.
The Director retains a separate moderation control for inappropriate posts.
