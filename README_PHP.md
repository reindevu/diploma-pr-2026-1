# Flour and Fire on PHP + Postgres

## Requirements

- PHP 8.1+
- PostgreSQL 14+

## Environment variables

Set these before running scripts or opening the site:

```bash
export DB_HOST=127.0.0.1
export DB_PORT=5432
export DB_NAME=flour_and_fire
export DB_USER=postgres
export DB_PASSWORD=postgres
```

Optional:

```bash
export DB_DSN="pgsql:host=127.0.0.1;port=5432;dbname=flour_and_fire"
```

## Database setup

Run:

```bash
php scripts/migrate.php
php scripts/seed.php
php scripts/make_admin.php admin@example.com Admin123!
```

`migrate.php` сейчас рассчитан на пустую базу и применяет первичную схему целиком.

## Local run

Use the built-in PHP server from the project root:

```bash
php -S 127.0.0.1:8000 -t public router.php
```

Then open:

- `http://127.0.0.1:8000/`
- `http://127.0.0.1:8000/admin`

## Docker run

The project can now be started through Docker Compose:

```bash
docker compose up --build
```

After startup:

- app: [http://127.0.0.1:10001](http://127.0.0.1:10001)
- admin: [http://127.0.0.1:10001/admin](http://127.0.0.1:10001/admin)
- postgres: `127.0.0.1:5432`

Default demo credentials from `docker-compose.yml`:

- admin email: `admin@example.com`
- admin password: `Admin123!`

On the first start the container:

- waits for PostgreSQL
- applies the initial schema if the DB is empty
- loads demo seed data
- creates or updates the admin user

## Current project structure

```text
app/                business logic, repositories, helpers
bootstrap/          app bootstrap
config/             configuration
database/           migrations and seeds
public/             web root, pages and static assets
resources/views/    reusable PHP views
scripts/            CLI utilities
entrypoint.sh       container startup script
```

## What is implemented

- Dynamic homepage and catalog from PostgreSQL
- Product page with size selection
- Registration and login with password hashing
- Session-based cart
- Promo code application
- Checkout and order creation
- Profile editing, password change, saved addresses, order history
- Admin page for products and order statuses

## Known limitations

- Product image upload is not implemented; admin uses image path string.
- Delivery fee is fixed in code at `200 ₽`.
- Promo code usage is validated through existing orders only.
- Contact content and map are still static.
