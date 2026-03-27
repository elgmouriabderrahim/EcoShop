# EcoShop API-First E-commerce Backend

EcoShop is an API-first platform for ecological products. It exposes REST endpoints for multiple clients (SPA, mobile, third-party integrations) using Laravel 13, Docker, Nginx, and PostgreSQL.

---

## Stack

* Laravel 13 (API backend)
* Laravel Sanctum (token authentication)
* PostgreSQL (database)
* Docker & Docker Compose (containerized environment)
* Nginx (web server)
* Events, Listeners, Queued Jobs (async processing)
* Pest (automated testing)

---

## Features

* User authentication: register, login, logout, profile
* Public catalog: products, product details, categories, category filtering
* Cart management: add/update/remove/view cart items
* Order creation from cart
* Asynchronous processing on order placement
* Admin endpoints for products, categories, and orders

---

## Architecture

* API routes in `src/routes/api.php`
* Validation through Form Request classes
* Role middleware (`admin`) for admin-only actions
* `OrderPlaced` event triggers:

  * `SendOrderConfirmationEmailJob`
  * `UpdateOrderStockJob` (processed asynchronously via queue worker)
* Dockerized services:

  * `app` (PHP-FPM / Laravel)
  * `web` (Nginx)
  * `db` (PostgreSQL)
  * `queue` (background worker)

---

## Quick Start (Docker)

1. Clone the project and go to docker folder:

```bash
cd docker
```

2. Configure environment:

```bash
cp ../src/.env.example ../src/.env
ln -s ../src/.env .env
```

3. Update `.env` (important for Docker):

```env
DB_HOST=db
DB_PORT=5432
```

4. Start all services:

```bash
docker compose up -d --build
```

5. Install dependencies inside container:

```bash
docker exec -it laravel_app composer install
```

6. Generate app key:

```bash
docker exec -it laravel_app php artisan key:generate
```

7. Run migrations and seeders:

```bash
docker exec -it laravel_app php artisan migrate --seed
```

---

## Application Access

* API base URL:
  `http://localhost:8000/api`

* pgAdmin:
  `http://localhost:5051`

---

## Queue System

The queue worker runs automatically in a dedicated Docker container:

```yaml
queue:
  command: php artisan queue:work
```

No manual command is required.

When a user places an order (`POST /api/orders`):

* Confirmation email is sent asynchronously
* Product stock is updated in background

---

## Run Tests (Pest)

1. Ensure containers are running:

```bash
docker compose up -d
```

2. Run tests inside container:

```bash
docker exec -it laravel_app ./vendor/bin/pest
```
or
```bash
docker exec -it laravel_app php artisan test
```

---

## Seeded Accounts

* Admin: `admin@ecoshop.test` / `password`
* Customer: `customer@ecoshop.test` / `password`

---

## API Documentation

* Detailed docs: `docs/API.md`
* Postman collection: `docs/EcoShop.postman_collection.json`

---

## Main Endpoints

### Auth

* `POST /api/auth/register`
* `POST /api/auth/login`
* `POST /api/auth/logout`
* `GET /api/auth/me`

### Catalog

* `GET /api/products`
* `GET /api/products/{product}`
* `GET /api/categories`

### Cart

* `GET /api/cart`
* `POST /api/cart`
* `PATCH /api/cart/{cart}`
* `DELETE /api/cart/{cart}`

### Orders

* `POST /api/orders`
* `GET /api/orders`

### Admin

* `GET /api/admin/products`
* `POST /api/admin/products`
* `GET /api/admin/categories`
* `POST /api/admin/categories`
* `GET /api/admin/orders`

---

## Security and Quality Notes

* Sanctum tokens protect private routes (`auth:sanctum`)
* Admin routes protected by `admin` middleware
* Form Request validation on write endpoints
* Proper HTTP status codes and JSON responses
* Pagination and eager loading used for performance

---

## Notes

* The application is fully containerized (no `php artisan serve`)
* Nginx handles HTTP requests and forwards them to PHP-FPM
* Queue worker runs in a separate container for async processing
* PostgreSQL runs in its own container with persistent storage
