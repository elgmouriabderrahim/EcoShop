# EcoShop API-First E-commerce Backend

EcoShop is an API-first platform for ecological products. It exposes REST endpoints for multiple clients (SPA, mobile, third-party integrations) with Laravel 13.

## Stack
- Laravel 13 (API backend)
- Laravel Sanctum (token authentication)
- Events, Listeners, Queued Jobs
- Pest (automated testing)

## Features
- User authentication: register, login, logout, profile
- Public catalog: products, product details, categories, category filtering
- Cart management: add/update/remove/view cart items
- Order creation from cart
- Asynchronous processing on order placement
- Admin endpoints for products, categories, and orders

## Architecture
- API routes in `src/routes/api.php`
- Validation through Form Request classes
- Role middleware (`admin`) for admin-only actions
- `OrderPlaced` event triggers listener and queued jobs:
	- `SendOrderConfirmationEmailJob`
	- `UpdateOrderStockJob`

## Quick Start
1. Go to Laravel app:
```bash
cd src
```
2. Install dependencies:
```bash
composer install
```
3. Configure environment:
```bash
cp .env.example .env
php artisan key:generate
```
4. Run migrations and seeders:
```bash
php artisan migrate --seed
```
5. Start app:
```bash
php artisan serve
```

API base URL: `http://localhost:8000/api`

## Queue Setup
Use database queue driver (default in this project):

```bash
php artisan queue:work
```

When a user places an order (`POST /api/orders`), queue workers process confirmation email and stock update jobs asynchronously.

## Run Tests (Pest)
```bash
./vendor/bin/pest
```

## Seeded Accounts
- Admin: `admin@ecoshop.test` / `password`
- Customer: `customer@ecoshop.test` / `password`

## API Documentation
- Detailed docs: `docs/API.md`
- Postman collection: `docs/EcoShop.postman_collection.json`

## Main Endpoints
- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/auth/me`
- `GET /api/products`
- `GET /api/products/{product}`
- `GET /api/categories`
- `GET /api/cart`
- `POST /api/cart`
- `PATCH /api/cart/{cart}`
- `DELETE /api/cart/{cart}`
- `POST /api/orders`
- `GET /api/orders`
- `GET /api/admin/products`
- `POST /api/admin/products`
- `GET /api/admin/categories`
- `POST /api/admin/categories`
- `GET /api/admin/orders`

## Security and Quality Notes
- Sanctum tokens protect private routes (`auth:sanctum`)
- Admin routes protected by dedicated `admin` middleware
- Form Request validation on write endpoints
- Proper HTTP status codes and JSON messages
- Pagination and eager loading used for performance