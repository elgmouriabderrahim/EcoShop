# EcoShop - Complete Project Breakdown from Zero

## Table of Contents
1. [Project Purpose](#project-purpose)
2. [Architecture Overview](#architecture-overview)
3. [Database Design](#database-design)
4. [Authentication & Authorization](#authentication--authorization)
5. [API Routes & Endpoints](#api-routes--endpoints)
6. [Data Flow Examples](#data-flow-examples)
7. [Component Deep Dive](#component-deep-dive)
8. [Async Processing](#async-processing)
9. [Testing Strategy](#testing-strategy)
10. [Docker Setup](#docker-setup)

---

## Project Purpose

**What is EcoShop?**
EcoShop is an **API-first e-commerce backend** for selling ecological/sustainable products. It's NOT a website you visit in a browser. Instead, it's a **REST API** that serves:
- Mobile apps
- Single Page Applications (SPAs)
- Third-party integrations

**Who Uses It?**
- **Customers**: Browse products, manage cart, place orders
- **Admins**: Manage products, categories, view all orders
- **API Clients**: Any app that makes HTTP requests to the API

**Key Features**
1. User registration and login
2. Browse products grouped by categories
3. Shopping cart management
4. Order placement with async processing
5. Admin panel for managing inventory
6. Automatic order confirmation emails
7. Stock updates when orders are confirmed

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                        CLIENT LAYER                          │
│  (Mobile App / Web Frontend / Third-party Integration)       │
└────────────────────────────┬────────────────────────────────┘
                             │ HTTP Requests (JSON)
                             ▼
┌─────────────────────────────────────────────────────────────┐
│                      API GATEWAY (Nginx)                     │
│                  Port 8000 -> Forwards to PHP                │
└────────────────────────────┬────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────┐
│                    APPLICATION LAYER (Laravel)               │
│  ┌───────────────────────────────────────────────────────┐  │
│  │ Routes Layer                                          │  │
│  │ - Route matching (GET /api/products -> Controller)   │  │
│  └───────────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────────┐  │
│  │ Middleware Layer                                      │  │
│  │ - Authentication (Sanctum tokens)                    │  │
│  │ - Authorization (Admin check)                        │  │
│  └───────────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────────┐  │
│  │ Validation Layer (FormRequest)                       │  │
│  │ - Check incoming data meets rules                    │  │
│  │ - Return 422 if invalid                              │  │
│  └───────────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────────┐  │
│  │ Controller Layer                                      │  │
│  │ - Business logic                                      │  │
│  │ - Interact with models                               │  │
│  │ - Return formatted responses                         │  │
│  └───────────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────────┐  │
│  │ Model Layer (Eloquent ORM)                           │  │
│  │ - Maps PHP objects to database tables                │  │
│  │ - Relationships between entities                     │  │
│  └───────────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────────┐  │
│  │ Event & Queue Layer                                  │  │
│  │ - Fire events on important actions                   │  │
│  │ - Dispatch jobs to queue                             │  │
│  └───────────────────────────────────────────────────────┘  │
└────────────┬────────────────────────────────────────────────┘
             │
    ┌────────┴────────┬────────────────┬────────────────┐
    ▼                 ▼                ▼                ▼
┌────────────┐  ┌────────────┐  ┌──────────────┐  ┌─────────┐
│ Database   │  │ Queue      │  │ Mail Service │  │ Logging │
│ (PostgreSQL)  │ (Database) │  │ (Mailtrap)   │  │         │
└────────────┘  └────────────┘  └──────────────┘  └─────────┘
```

---

## Database Design

### Tables and Relationships

```
USERS
├── id (Primary Key)
├── full_name
├── email (Unique)
├── password (hashed)
├── role ('admin' or 'customer')
├── email_verified_at
├── created_at, updated_at
└── Relationships:
    ├── hasMany(Cart)
    ├── hasMany(Order)
    └── hasMany(PersonalAccessToken)

CATEGORIES
├── id (Primary Key)
├── name (Unique)
├── created_at, updated_at
└── Relationships:
    └── hasMany(Product)

PRODUCTS
├── id (Primary Key)
├── category_id (Foreign Key)
├── name
├── description
├── price (decimal 10,2)
├── stock (integer)
├── created_at, updated_at
└── Relationships:
    ├── belongsTo(Category)
    ├── hasMany(Cart)
    └── hasMany(OrderItem)

CARTS
├── id (Primary Key)
├── user_id (Foreign Key)
├── product_id (Foreign Key)
├── quantity (integer)
├── created_at, updated_at
├── Unique: (user_id, product_id) - One cart row per user per product
└── Relationships:
    ├── belongsTo(User)
    └── belongsTo(Product)

ORDERS
├── id (Primary Key)
├── user_id (Foreign Key)
├── status ('pending' or 'confirmed')
├── total_amount (decimal 12,2)
├── created_at, updated_at
└── Relationships:
    ├── belongsTo(User)
    └── hasMany(OrderItem)

ORDER_ITEMS
├── id (Primary Key)
├── order_id (Foreign Key)
├── product_id (Foreign Key)
├── quantity (integer)
├── unit_price (decimal 10,2) - Snapshot at order time
├── created_at, updated_at
└── Relationships:
    ├── belongsTo(Order)
    └── belongsTo(Product)

PERSONAL_ACCESS_TOKENS (Sanctum)
├── id (Primary Key)
├── tokenable_type ('App\\Models\\User')
├── tokenable_id (Foreign Key)
├── name ('api-token')
├── token (unique, hashed)
├── abilities (nullable)
├── last_used_at, expires_at
├── created_at, updated_at
└── Relationships:
    └── User has many tokens

JOBS (Queue storage)
├── id (Primary Key)
├── queue
├── payload (serialized job data)
├── attempts
├── reserved_at, available_at
├── created_at
```

### Why This Structure?

1. **Normalization**: Each table represents ONE entity type
2. **Foreign Keys**: Links between tables prevent data duplication
3. **Constraints**: 
   - Unique email prevents duplicate user accounts
   - Unique (user_id, product_id) in carts prevents "add same product to cart twice"
4. **Cascading Deletes**: If user deletes account, cart/orders auto-delete
5. **Snapshots**: ORDER_ITEMS preserves product price at purchase time (prices can change)

---

## Authentication & Authorization

### How Authentication Works

**Step 1: Registration**
```
POST /api/auth/register
{
  "full_name": "Alice Green",
  "email": "alice@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Inside `src/app/Http/Controllers/Api/AuthController.php`:**
```php
public function register(RegisterRequest $request): JsonResponse
{
    // $request is ALREADY validated by RegisterRequest
    $user = User::query()->create([
        'full_name' => $request->string('full_name')->toString(),
        'email' => $request->string('email')->toString(),
        'password' => $request->string('password')->toString(),  // Auto-hashed by User model
        'role' => 'customer',
    ]);

    // Create a Bearer token for this user
    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'message' => 'Registration successful.',
        'token' => $token,
        'user' => $user,
    ], 201);
}
```

**Response:**
```json
{
  "message": "Registration successful.",
  "token": "1|abc123def456ghi789jkl",
  "user": {
    "id": 1,
    "full_name": "Alice Green",
    "email": "alice@example.com",
    "role": "customer",
    "created_at": "2026-03-27T10:00:00Z",
    "updated_at": "2026-03-27T10:00:00Z"
  }
}
```

**Step 2: Login**
```
POST /api/auth/login
{
  "email": "alice@example.com",
  "password": "password123"
}
```

**Inside AuthController:**
```php
public function login(LoginRequest $request): JsonResponse
{
    // Find user by email
    $user = User::query()->where('email', $request->string('email')->toString())->first();

    // Check password matches hash
    if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
        return response()->json([
            'message' => 'Invalid credentials.',
        ], 422);
    }

    // Issue new token
    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'message' => 'Login successful.',
        'token' => $token,
        'user' => $user,
    ]);
}
```

### How Token Auth Works (Sanctum)

**Client stores the token:**
```
Header: Authorization: Bearer 1|abc123def456ghi789jkl
```

**When making authenticated request:**
```
GET /api/auth/me
Authorization: Bearer 1|abc123def456ghi789jkl
```

**In middleware `src/bootstrap/app.php`:**
```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
    ]);
})
```

**Middleware flow:**
1. Request comes in with Bearer token
2. `auth:sanctum` middleware (built into Laravel) validates token
3. If valid, `request()->user()` returns the User object
4. If invalid, returns 401 Unauthorized

**In route `src/routes/api.php`:**
```php
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
});
```

### Authorization (Admin Middleware)

**Custom Middleware in `src/app/Http/Middleware/EnsureUserIsAdmin.php`:**
```php
public function handle(Request $request, Closure $next): Response
{
    // Check: (1) User is authenticated, (2) User.role === 'admin'
    if (! $request->user() || ! $request->user()->isAdmin()) {
        return new JsonResponse([
            'message' => 'Forbidden. Admin access required.',
        ], 403);
    }

    return $next($request);
}
```

**In User model `src/app/Models/User.php`:**
```php
public function isAdmin(): bool
{
    return $this->role === 'admin';
}
```

**Routes protected:**
```php
Route::middleware('admin')->prefix('admin')->group(function (): void {
    Route::apiResource('products', ProductManagementController::class);
    Route::apiResource('categories', CategoryManagementController::class);
    Route::get('/orders', [OrderManagementController::class, 'index']);
});
```

**So to access `/api/admin/products`:**
1. Must have valid Bearer token
2. Token must belong to user with role = 'admin'
3. Returns 403 if not admin

---

## API Routes & Endpoints

### Complete Route Map (from `src/routes/api.php`)

```
AUTH ROUTES (No auth needed for register/login)
├── POST /api/auth/register         → Create new user + get token
├── POST /api/auth/login            → Authenticate + get token
└── Protected (require token):
    ├── POST /api/auth/logout       → Delete current token
    └── GET /api/auth/me            → Get logged-in user profile

PUBLIC CATALOG (No auth needed)
├── GET /api/products?category_id=1&per_page=15    → List all products (paginated, filterable)
├── GET /api/products/{product}                     → Get single product details
└── GET /api/categories                            → List all categories

CART ROUTES (require token)
├── GET /api/cart                   → View user's cart items
├── POST /api/cart                  → Add/update item in cart
├── PATCH /api/cart/{cart}          → Update quantity of cart item
└── DELETE /api/cart/{cart}         → Remove item from cart

ORDER ROUTES (require token)
├── POST /api/orders                → Create order from cart (triggers async jobs)
└── GET /api/orders                 → Get user's order history

ADMIN ROUTES (require token + admin role)
├── PRODUCTS
│   ├── GET /api/admin/products                     → List all products
│   ├── POST /api/admin/products                    → Create new product
│   ├── GET /api/admin/products/{product}           → Get product details
│   ├── PUT /api/admin/products/{product}           → Replace product
│   ├── PATCH /api/admin/products/{product}         → Partial update
│   └── DELETE /api/admin/products/{product}        → Delete product
├── CATEGORIES
│   ├── GET /api/admin/categories                   → List all categories
│   ├── POST /api/admin/categories                  → Create new category
│   ├── GET /api/admin/categories/{category}        → Get category details
│   ├── PUT /api/admin/categories/{category}        → Replace category
│   ├── PATCH /api/admin/categories/{category}      → Partial update
│   └── DELETE /api/admin/categories/{category}     → Delete category
└── ORDERS (read-only)
    ├── GET /api/admin/orders                       → List all orders
    └── GET /api/admin/orders/{order}               → Get order with items
```

---

## Data Flow Examples

### Example 1: Customer Registration and Token

**Step 1: Client sends registration request**
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "Alice Green",
    "email": "alice@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Step 2: Request hits route**
→ `routes/api.php`: `Route::post('/register', [AuthController::class, 'register']);`

**Step 3: Validation**
→ `RegisterRequest.php` checks rules:
```php
'full_name' => ['required', 'string', 'max:255'],
'email' => ['required', 'email', 'max:255', 'unique:users,email'],
'password' => ['required', 'string', 'min:8', 'confirmed'],
```
✅ Passes → Request object with validated data sent to controller
❌ Fails → Returns 422 with errors

**Step 4: Controller creates user**
```php
$user = User::query()->create([
    'full_name' => 'Alice Green',
    'email' => 'alice@example.com',
    'password' => 'password123',  // Model casts: 'hashed'
    'role' => 'customer',
]);
// Database receives:
// INSERT INTO users (full_name, email, password, role) 
// VALUES ('Alice Green', 'alice@example.com', '$2y$04$...hashed...', 'customer')
```

**Step 5: Create token**
```php
$token = $user->createToken('api-token')->plainTextToken;
// This calls Sanctum, which:
// - Generates random token
// - Hashes it and stores in personal_access_tokens table
// - Returns unhashed version to client
// Example token: 1|abc123def456ghi789jkl
//                |__ token ID
//                   |__ plaintext token (only shown once)
```

**Step 6: Return response**
```json
{
  "message": "Registration successful.",
  "token": "1|abc123def456ghi789jkl",
  "user": {
    "id": 1,
    "full_name": "Alice Green",
    "email": "alice@example.com",
    "role": "customer"
  }
}
```

**Step 7: Client stores token**
Client saves `1|abc123def456ghi789jkl` in local storage/cookies and uses it for future requests.

---

### Example 2: Browse Products with Filtering

**Client request:**
```bash
curl http://localhost:8000/api/products?category_id=2&per_page=10
```

**Route:** `GET /api/products` → `ProductController@index()`

**Validation (IndexProductRequest):**
```php
'category_id' => ['nullable', 'integer', 'exists:categories,id'],
'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
```
→ Checks category_id is valid if provided, per_page is 1-100

**Controller logic:**
```php
public function index(IndexProductRequest $request): AnonymousResourceCollection
{
    $products = Product::query()
        ->with('category')  // Eager-load category (prevents N+1 queries)
        ->when($request->filled('category_id'), function ($query) use ($request): void {
            $query->where('category_id', $request->integer('category_id'));
        })
        ->latest('id')  // Sort by newest first
        ->paginate($request->integer('per_page', 15));  // Default 15 per page

    return ProductResource::collection($products);
}
```

**What happens:**
1. Query builds: `SELECT * FROM products WHERE category_id = 2 ORDER BY id DESC LIMIT 15`
2. Eager-load: Also loads category for each product
3. Pagination: Adds offset/limit and returns meta (total, pages, etc.)

**Response (ProductResource transforms data):**
```json
{
  "data": [
    {
      "id": 5,
      "category": {
        "id": 2,
        "name": "Zero Waste"
      },
      "name": "Bamboo Toothbrush",
      "description": "Sustainable bamboo toothbrush with biodegradable bristles",
      "price": 5.49,
      "stock": 42,
      "created_at": "2026-03-20T08:30:00Z",
      "updated_at": "2026-03-20T08:30:00Z"
    },
    {
      "id": 6,
      "category": {
        "id": 2,
        "name": "Zero Waste"
      },
      "name": "Reusable Water Bottle",
      "description": "Stainless steel reusable bottle",
      "price": 19.99,
      "stock": 30,
      "created_at": "2026-03-21T10:15:00Z",
      "updated_at": "2026-03-21T10:15:00Z"
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/products?category_id=2&page=1",
    "last": "http://localhost:8000/api/products?category_id=2&page=3",
    "prev": null,
    "next": "http://localhost:8000/api/products?category_id=2&page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 3,
    "path": "http://localhost:8000/api/products",
    "per_page": 10,
    "to": 10,
    "total": 30
  }
}
```

---

### Example 3: Add Item to Cart

**Client request (with token):**
```bash
curl -X POST http://localhost:8000/api/cart \
  -H "Authorization: Bearer 1|abc123def456ghi789jkl" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 5,
    "quantity": 2
  }'
```

**Route:** `POST /api/cart` → `CartController@store()`

**Middleware chain:**
1. `auth:sanctum` validates token → sets `request()->user()` = Alice (user_id=1)
2. Request passes to controller

**Validation (StoreCartItemRequest):**
```php
'product_id' => ['required', 'integer', 'exists:products,id'],
'quantity' => ['required', 'integer', 'min:1'],
```
→ Checks product exists and quantity >= 1

**Controller logic:**
```php
public function store(StoreCartItemRequest $request): CartResource
{
    $product = Product::query()->findOrFail($request->integer('product_id'));
    // $product = Bamboo Toothbrush (stock=42)

    if ($request->integer('quantity') > $product->stock) {
        response()->json(['message' => 'Requested quantity exceeds available stock.'], 422)->throwResponse();
    }
    // 2 <= 42 ✅

    $cart = Cart::query()->updateOrCreate(
        [
            'user_id' => request()->user()->id,  // 1 (Alice)
            'product_id' => $product->id,         // 5
        ],
        [
            'quantity' => $request->integer('quantity'),  // 2
        ]
    );
    // This query:
    // - Looks for: user_id=1 AND product_id=5
    // - If exists: UPDATE quantity = 2
    // - If NOT exists: INSERT new row
    // Unique constraint (user_id, product_id) ensures only one row per user per product

    $cart->load('product.category');

    return new CartResource($cart);
}
```

**Response (CartResource):**
```json
{
  "id": 7,
  "quantity": 2,
  "product": {
    "id": 5,
    "category": {
      "id": 2,
      "name": "Zero Waste"
    },
    "name": "Bamboo Toothbrush",
    "description": "Sustainable bamboo toothbrush with biodegradable bristles",
    "price": 5.49,
    "stock": 42,
    "created_at": "2026-03-20T08:30:00Z",
    "updated_at": "2026-03-20T08:30:00Z"
  },
  "line_total": 10.98,
  "created_at": "2026-03-27T14:22:00Z",
  "updated_at": "2026-03-27T14:22:00Z"
}
```

**What's in database now:**
```
carts table:
id | user_id | product_id | quantity | created_at | updated_at
7  | 1       | 5          | 2        | ...        | ...
```

---

### Example 4: Place Order (The Complex One)

**Client request:**
```bash
curl -X POST http://localhost:8000/api/orders \
  -H "Authorization: Bearer 1|abc123def456ghi789jkl"
```

**Route:** `POST /api/orders` → `OrderController@store()`

**Middleware:** `auth:sanctum` → Alice is authenticated

**Controller logic (this is where it gets complex):**

```php
public function store(): JsonResponse
{
    $user = request()->user();  // Alice (id=1)

    // Get all items in Alice's cart with product details
    $cartItems = Cart::query()
        ->with('product')
        ->where('user_id', $user->id)
        ->get();
    // Result: Collection with 1 item (Bamboo Toothbrush x2)

    if ($cartItems->isEmpty()) {
        return response()->json(['message' => 'Cart is empty.'], 422);
    }

    // Validate stock is available for all items
    foreach ($cartItems as $item) {
        if ($item->quantity > $item->product->stock) {
            return response()->json([
                'message' => "Insufficient stock for product {$item->product->name}.",
            ], 422);
        }
    }
    // All checks pass

    // HERE'S THE CRITICAL PART: DB TRANSACTION
    // This ensures if ANYTHING fails, EVERYTHING rolls back
    $order = DB::transaction(function () use ($user, $cartItems) {
        // Calculate total
        $total = $cartItems->sum(fn ($item): float => (float) ($item->quantity * $item->product->price));
        // $total = 2 * 5.49 = 10.98

        // Step 1: Create order with status 'pending'
        $order = Order::query()->create([
            'user_id' => $user->id,         // 1
            'status' => 'pending',          // Will be 'confirmed' after stock updated
            'total_amount' => $total,       // 10.98
        ]);
        // Database INSERT:
        // INSERT INTO orders (user_id, status, total_amount) 
        // VALUES (1, 'pending', 10.98)

        // Step 2: Create order items (preserving prices at order time)
        foreach ($cartItems as $item) {
            $order->orderItems()->create([
                'product_id' => $item->product_id,    // 5
                'quantity' => $item->quantity,        // 2
                'unit_price' => $item->product->price, // 5.49 (snapshot!)
            ]);
        }
        // Database INSERT:
        // INSERT INTO order_items (order_id, product_id, quantity, unit_price)
        // VALUES (12, 5, 2, 5.49)

        // Step 3: Clear Alice's cart
        Cart::query()->where('user_id', $user->id)->delete();
        // Database DELETE:
        // DELETE FROM carts WHERE user_id = 1
        // Removes the cart row with Bamboo Toothbrush

        return $order;
    });
    // If ANY step fails, entire transaction rolls back

    $order->load(['user', 'orderItems.product.category']);

    // FIRE EVENT (after successful transaction)
    event(new OrderPlaced($order));
    // This triggers the async processing pipeline

    return response()->json([
        'message' => 'Order placed successfully.',
        'data' => new OrderResource($order),
    ], 201);
}
```

**Why the transaction?**
Imagine:
- Order created ✅
- Order items created ✅
- But: Cart deletion FAILS ❌

Without transaction: Cart still has items, but order exists → user can order same items again
With transaction: Everything rolls back, cart still has items, user can retry

**Response:**
```json
{
  "message": "Order placed successfully.",
  "data": {
    "id": 12,
    "status": "pending",
    "total_amount": 10.98,
    "user": {
      "id": 1,
      "full_name": "Alice Green",
      "email": "alice@example.com"
    },
    "items": [
      {
        "id": 8,
        "quantity": 2,
        "unit_price": 5.49,
        "product": {
          "id": 5,
          "category": {
            "id": 2,
            "name": "Zero Waste"
          },
          "name": "Bamboo Toothbrush",
          "description": "Sustainable bamboo toothbrush with biodegradable bristles",
          "price": 5.49,
          "stock": 42,
          "created_at": "2026-03-20T08:30:00Z",
          "updated_at": "2026-03-20T08:30:00Z"
        }
      }
    ],
    "created_at": "2026-03-27T14:30:00Z",
    "updated_at": "2026-03-27T14:30:00Z"
  }
}
```

**What's in database now:**
```
orders:
id | user_id | status  | total_amount | created_at | updated_at
12 | 1       | pending | 10.98        | ...        | ...

order_items:
id | order_id | product_id | quantity | unit_price | created_at
8  | 12       | 5          | 2        | 5.49       | ...

carts: (EMPTY - row deleted)
```

---

## Component Deep Dive

### 1. FormRequest Validation

**File: `src/app/Http/Requests/Auth/RegisterRequest.php`**

```php
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;  // Anyone can register (no auth check needed)
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
```

**What each rule means:**
- `required`: Field must be present
- `string`: Field must be text
- `max:255`: Max 255 characters
- `email`: Must be valid email format
- `unique:users,email`: Email must not exist in users table
- `min:8`: Password at least 8 characters
- `confirmed`: Must have `password_confirmation` field that matches

**When validation fails:**
```json
{
  "message": "The email field must be a valid email address. (and 2 more errors)",
  "errors": {
    "email": ["The email field must be a valid email address."],
    "password": ["The password field must be at least 8 characters."]
  }
}
```
Status: 422 Unprocessable Entity

---

### 2. Models and Relationships

**File: `src/app/Models/User.php`**

```php
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    #[Fillable(['full_name', 'email', 'password', 'role'])]
    #[Hidden(['password', 'remember_token'])]

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',  // Auto-hash on assignment
        ];
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
```

**What this means:**

1. `#[Fillable(...)]`: These fields can be mass-assigned via `User::create([])`
2. `#[Hidden(...)]`: These fields NEVER appear in JSON responses (even if loaded)
3. `casts['password'] = 'hashed'`: When you do `$user->password = 'plaintext'`, it auto-hashes
4. `hasMany(Cart::class)`: User has many carts (`$user->carts`)
5. `isAdmin()`: Helper method to check if admin

**File: `src/app/Models/Product.php`**

```php
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price',
        'stock',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',  // Always 2 decimal places
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
```

**Relationship Usage:**

```php
$product = Product::find(5);
$product->category;        // Returns Category object for category_id
$product->carts;           // Returns all Cart rows linking to product 5
$product->orderItems;      // Returns all OrderItem rows for product 5
```

---

### 3. Controllers and Business Logic

**File: `src/app/Http/Controllers/Api/CartController.php`**

```php
public function index(): JsonResponse
{
    $items = Cart::query()
        ->with('product.category')  // Eager-load to avoid N+1 queries
        ->where('user_id', request()->user()->id)
        ->get();

    // Calculate total manually
    $total = $items->sum(fn (Cart $item): float => (float) ($item->quantity * $item->product->price));

    return response()->json([
        'data' => CartResource::collection($items),
        'meta' => [
            'total_amount' => $total,
        ],
    ]);
}
```

**Why separate response structure?**
- `data`: Array of items
- `meta`: Aggregated info (total)

**File: `src/app/Http/Controllers/Api/Admin/ProductManagementController.php`**

```php
public function store(StoreProductRequest $request): JsonResponse
{
    // Validation ALREADY done by FormRequest
    $product = Product::query()->create($request->validated());
    $product->load('category');

    // Convert to API response
    return (new ProductResource($product))->response()->setStatusCode(201);
}

public function update(UpdateProductRequest $request, Product $product): ProductResource
{
    // Route model binding: {product} automatically injects Product by id
    $product->update($request->validated());
    $product->load('category');

    return new ProductResource($product);
}

public function destroy(Product $product): JsonResponse
{
    $product->delete();

    return response()->json([
        'message' => 'Product deleted successfully.',
    ]);
}
```

**Route Model Binding:**
```
DELETE /api/admin/products/5

Route definition:
Route::delete('/products/{product}', [ProductManagementController::class, 'destroy']);

Laravel auto-resolves {product} to:
Product::find(5);

And injects as parameter to destroy()
```

---

### 4. API Resources (Response Formatting)

**File: `src/app/Http/Resources/ProductResource.php`**

```php
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'stock' => $this->stock,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

**What `whenLoaded()` does:**
```php
// If category was eager-loaded
$product->load('category');
// Then: new CategoryResource($this->whenLoaded('category')) appears in response

// If category was NOT loaded
$product = Product::find(5);  // No eager-load
// Then: new CategoryResource($this->whenLoaded('category')) is OMITTED from response
```

**Before Resource:**
```json
{
  "id": 5,
  "category_id": 2,
  "name": "Bamboo Toothbrush",
  "password": "$2y$04$...",  // LEAKED!
  "remember_token": "abc123",  // LEAKED!
  "pivot": { ... }  // Internal data leaked
}
```

**After Resource:**
```json
{
  "id": 5,
  "category": {
    "id": 2,
    "name": "Zero Waste"
  },
  "name": "Bamboo Toothbrush",
  "description": "...",
  "price": 5.49,
  "stock": 42,
  "created_at": "2026-03-20T08:30:00Z",
  "updated_at": "2026-03-20T08:30:00Z"
}
```

---

## Async Processing

### The Order -> Email -> Stock Update Pipeline

**File: `src/app/Events/OrderPlaced.php`**

```php
class OrderPlaced
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order)
    {
    }
}
```

**What happens when event fires:**
```php
// In OrderController
event(new OrderPlaced($order));
```

**Step 1: Event Registration**
File: `src/app/Providers/EventServiceProvider.php`

```php
protected $listen = [
    OrderPlaced::class => [
        ProcessPlacedOrder::class,  // When OrderPlaced fires, run this listener
    ],
];
```

**Step 2: Listener Dispatches Jobs**
File: `src/app/Listeners/ProcessPlacedOrder.php`

```php
class ProcessPlacedOrder
{
    public function handle(OrderPlaced $event): void
    {
        // Don't run email/stock update in this request
        // Instead, create jobs in the queue
        SendOrderConfirmationEmailJob::dispatch($event->order->id);
        UpdateOrderStockJob::dispatch($event->order->id);
    }
}
```

**Step 3: Jobs Stay in Database**
File: `src/config/queue.php`

```php
'default' => env('QUEUE_CONNECTION', 'database'),

'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => env('DB_QUEUE_TABLE', 'jobs'),
        'queue' => 'default',
        'retry_after' => 90,
    ],
]
```

**What's in the jobs table:**
```
jobs table:
id | queue   | payload                  | attempts | available_at | created_at
1  | default | {...serialized job...}   | 0        | 1711532000   | 1711531900
2  | default | {...serialized job...}   | 0        | 1711532000   | 1711531900
```

**Step 4: Queue Worker Processes**
```bash
php artisan queue:work --tries=1 --timeout=0
```

This daemon listens to the jobs table:
1. Finds jobs with `available_at <= now()`
2. Deserializes job
3. Runs the job's `handle()` method
4. Deletes job from table

**File: `src/app/Jobs/SendOrderConfirmationEmailJob.php`**

```php
class SendOrderConfirmationEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId)
    {
    }

    public function handle(): void
    {
        $order = Order::query()->with(['user', 'orderItems.product'])->find($this->orderId);

        if (! $order) {
            return;  // Order was deleted, skip
        }

        Mail::to($order->user->email)->send(new OrderConfirmationMail($order));
    }
}
```

**File: `src/app/Mail/OrderConfirmationMail.php`**

```php
class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your EcoShop Order Confirmation'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmation'
        );
    }
}
```

**File: `src/resources/views/emails/order-confirmation.blade.php`**

```blade
<h1>Thank you for your EcoShop order, {{ $order->user->full_name }}.</h1>
<p>Your order #{{ $order->id }} has been received and is being processed.</p>
<p>Total: ${{ number_format($order->total_amount, 2) }}</p>
<p>We appreciate your support for ecological products.</p>
```

**File: `src/app/Jobs/UpdateOrderStockJob.php`**

```php
class UpdateOrderStockJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId)
    {
    }

    public function handle(): void
    {
        $order = Order::query()->with('orderItems.product')->find($this->orderId);

        if (! $order || $order->status !== 'pending') {
            return;  // Order deleted or already processed
        }

        DB::transaction(function () use ($order): void {
            foreach ($order->orderItems as $item) {
                $product = $item->product;

                if (! $product) {
                    continue;  // Product deleted, skip
                }

                // Decrease stock by order quantity
                $product->decrement('stock', $item->quantity);
                // Equivalent to: UPDATE products SET stock = stock - 2 WHERE id = 5
            }

            // Order is now confirmed
            $order->update(['status' => 'confirmed']);
        });
    }
}
```

### Timeline

```
Client makes POST /api/orders
     ↓
[Request] (FAST - 200ms)
     ↓
OrderController validates & creates order (status='pending')
     ↓
Event(OrderPlaced) is dispatched
     ↓
Listener creates 2 jobs in queue table
     ↓
[RESPONSE: 201] Order created successfully
     ↓
--- ASYNC (happens later) ---
     ↓
Queue worker picks up SendOrderConfirmationEmailJob
     ↓
Sends email to customer
     ↓
Job deleted from queue
     ↓
Queue worker picks up UpdateOrderStockJob
     ↓
Decrements product stock
     ↓
Sets order status = 'confirmed'
     ↓
Job deleted from queue
```

**Why async?**
- Sending email takes 1-2 seconds (API to email service)
- Synchronous: customer waits 2+ seconds for response
- Asynchronous: customer gets instant response, email sent in background

---

## Testing Strategy

### Feature Tests (Integration Tests)

**File: `src/tests/Feature/AuthApiTest.php`**

```php
it('registers a user and returns token', function (): void {
    $response = $this->postJson('/api/auth/register', [
        'full_name' => 'Alice Green',
        'email' => 'alice@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    // Check response structure
    $response
        ->assertCreated()  // HTTP 201
        ->assertJsonStructure([
            'message',
            'token',
            'user' => ['id', 'full_name', 'email', 'role'],
        ]);

    // Check database
    expect(User::query()->where('email', 'alice@example.com')->exists())->toBeTrue();
});
```

**What this test does:**
1. Makes HTTP POST request to `/api/auth/register`
2. Checks response is 201 Created
3. Checks response JSON has expected structure
4. Verifies user was actually created in database

**File: `src/tests/Feature/OrderCreationTest.php`**

```php
it('creates order from cart and dispatches queued jobs', function (): void {
    Queue::fake();  // Don't actually send jobs to queue

    $user = User::factory()->customer()->create();
    $product = Product::factory()->create(['stock' => 30, 'price' => 12.50]);

    Cart::query()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    Sanctum::actingAs($user);

    $this->postJson('/api/orders')
        ->assertCreated()
        ->assertJsonPath('message', 'Order placed successfully.')
        ->assertJsonPath('data.status', 'pending');

    expect(Cart::query()->where('user_id', $user->id)->exists())->toBeFalse();

    Queue::assertPushed(SendOrderConfirmationEmailJob::class);
    Queue::assertPushed(UpdateOrderStockJob::class);
});
```

**What this test does:**
1. Fakes the queue (prevents real jobs being queued)
2. Creates test user and product
3. Simulates user making order request
4. Verifies order was created with 'pending' status
5. Verifies cart was cleared
6. Verifies jobs were dispatched to queue

**File: `src/tests/Feature/AdminProductManagementTest.php`**

```php
it('allows admin to create a product', function (): void {
    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();

    Sanctum::actingAs($admin);

    $this->postJson('/api/admin/products', [
        'category_id' => $category->id,
        'name' => 'Bamboo Toothbrush',
        'description' => 'Sustainable bamboo toothbrush.',
        'price' => 5.49,
        'stock' => 25,
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Bamboo Toothbrush');

    expect(Product::query()->where('name', 'Bamboo Toothbrush')->exists())->toBeTrue();
});

it('prevents non admin from creating a product', function (): void {
    $user = User::factory()->customer()->create();
    $category = Category::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/admin/products', [
        'category_id' => $category->id,
        'name' => 'Reusable Bottle',
        'price' => 19.99,
        'stock' => 20,
    ])->assertForbidden();  // HTTP 403
});
```

**Key testing patterns:**
- `Queue::fake()`: Prevents actual queue processing
- `Sanctum::actingAs($user)`: Simulate authenticated user
- `assertCreated()`, `assertOk()`, `assertForbidden()`: HTTP status checks
- `assertJsonPath('data.name', 'value')`: Check specific JSON values
- `expect(...)->toBeTrue()`: Pest assertion syntax (modern PHP testing)

### Test Configuration

**File: `src/phpunit.xml`**

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="pgsql"/>
    <env name="DB_HOST" value="127.0.0.1"/>
    <env name="DB_PORT" value="5434"/>
    <env name="DB_DATABASE" value="ecoshop_test"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="MAIL_MAILER" value="array"/>
</php>
```

**What each setting means:**
- `APP_ENV=testing`: Framework knows we're testing (uses testing config)
- `DB_CONNECTION=pgsql`: Use PostgreSQL for tests
- `QUEUE_CONNECTION=sync`: Jobs run immediately (for testing)
- `MAIL_MAILER=array`: Emails stored in memory (not sent)

**Pest Setup File: `src/tests/Pest.php`**

```php
uses(Tests\TestCase::class, RefreshDatabase::class)->in('Feature');
```

- `TestCase::class`: Base test class with HTTP helpers
- `RefreshDatabase::class`: Before each test, refresh database to clean state

---

## Docker Setup

### Container Architecture

**File: `docker/docker-compose.yml`**

```yaml
services:
  app:
    build:
      context: ..
      dockerfile: docker/Dockerfile
    container_name: laravel_app
    working_dir: /var/www
    volumes:
      - ../src:/var/www  # Mount local src/ to container /var/www
    depends_on:
      - db  # Wait for db to start
    networks:
      - laravel

  web:
    image: nginx:alpine
    container_name: laravel_nginx
    ports:
      - "8000:80"  # Host 8000 -> Container 80 (Nginx)
    volumes:
      - ../src:/var/www
      - ./nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app

  db:
    image: postgres:17
    container_name: laravel_db
    environment:
      POSTGRES_DB: ${DB_DATABASE}
      POSTGRES_USER: ${DB_USERNAME}
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    ports:
      - "5434:5432"  # Host 5434 -> Container 5432 (PostgreSQL)
    volumes:
      - pgdata:/var/lib/postgresql/data

  pgadmin:
    image: dpage/pgadmin4
    ports:
      - "5051:80"
    depends_on:
      - db
```

**Container flow:**

```
Client HTTP
    ↓ :8000
┌─────────────────────────────┐
│ web (nginx:alpine)          │
│ - Listens on port 80        │
│ - Forwards .php to app:9000 │
└─────────────┬───────────────┘
              │ fastcgi_pass app:9000
┌─────────────▼───────────────┐
│ app (php:8.4-fpm)           │
│ - Runs Laravel app          │
│ - Connects to db:5432       │
└─────────────┬───────────────┘
              │ PDO/pgsql
┌─────────────▼───────────────┐
│ db (postgres:17)            │
│ - Database server           │
│ - Stores all data           │
└─────────────────────────────┘
```

**File: `docker/Dockerfile`**

```dockerfile
FROM php:8.4-fpm

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \  # PostgreSQL client libs
    libzip-dev \
    zip \
    curl

RUN docker-php-ext-install \
    pdo \           # PDO database extension
    pdo_pgsql \     # PostgreSQL driver for PDO
    pgsql \         # PostgreSQL native extension
    zip             # ZIP handling

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN groupadd -g ${GID} laravel \
    && useradd -u ${UID} -g laravel -m laravel

WORKDIR /var/www

RUN chown -R laravel:laravel /var/www

USER laravel
```

**Key points:**
- `pdo_pgsql`: Allows Laravel to connect to PostgreSQL
- `Composer`: Package manager for PHP dependencies
- `laravel` user: Runs app with limited permissions (security)

**File: `docker/nginx/default.conf`**

```nginx
server {
    listen 80;
    index index.php index.html;
    root /var/www/public;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ .php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

**What this config does:**

1. **Root directory:** `/var/www/public` (Laravel's public folder)
2. **Static files:** Try to serve as static files
3. **Unknown URLs:** Rewrite to `/index.php?$query_string` (Laravel bootstrap)
4. **PHP requests:** Forward to FPM at `app:9000`

**Example request flow:**

```
Client: GET http://localhost:8000/api/products

Nginx:
1. Check if /var/www/public/api/products exists → NO
2. Check if /var/www/public/api/products/ exists → NO
3. Rewrite to: /index.php?$query_string
4. Pass to PHP-FPM at app:9000

PHP-FPM:
1. Bootstrap Laravel app (index.php)
2. Route dispatcher: GET /api/products
3. Find matching route → ProductController@index
4. Execute controller logic
5. Return JSON response
```

### Running with Docker

```bash
# Start containers
docker compose up -d

# Run migrations
docker exec laravel_app php artisan migrate --seed

# View logs
docker compose logs -f app

# Stop containers
docker compose down
```

---

## Summary: Why This Architecture?

| Component | Why? |
|-----------|------|
| **REST API** | Decouples frontend from backend; multiple clients can use same API |
| **Sanctum Tokens** | Stateless auth (no sessions); ideal for mobile and SPAs |
| **FormRequest Validation** | Centralized validation logic; consistent 422 responses |
| **Eloquent ORM** | Type-safe model access; built-in relationships and eager loading |
| **Events + Queues** | Async side effects; keeps endpoints fast; improves scalability |
| **Database Transactions** | Atomic operations; prevents partial writes |
| **Resources** | Stable response format; hides internal model structure |
| **Feature Tests** | Integration testing; verifies full request-response cycles |
| **Docker** | Reproducible environment; same setup locally and in production |
| **PostgreSQL** | Robust ACID compliance; supports complex queries |

---

## File Structure Reference

```
src/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/          ← Request handlers (business logic)
│   │   ├── Middleware/               ← Auth, authorization checks
│   │   ├── Requests/                 ← Input validation rules
│   │   └── Resources/                ← Response formatting
│   ├── Models/                       ← Database entities & relationships
│   ├── Events/                       ← Event definitions
│   ├── Listeners/                    ← Event handlers
│   ├── Jobs/                         ← Queued background tasks
│   ├── Mail/                         ← Email class definitions
│   └── Providers/                    ← Service registration (EventServiceProvider)
├── routes/
│   └── api.php                       ← All API endpoints
├── database/
│   ├── migrations/                   ← Schema definitions
│   ├── seeders/                      ← Populate initial data
│   └── factories/                    ← Generate fake data for tests
├── resources/
│   └── views/
│       └── emails/                   ← Email templates
├── tests/
│   └── Feature/                      ← Integration tests
├── config/
│   ├── auth.php                      ← Auth configuration
│   ├── queue.php                     ← Queue driver config
│   └── ...
└── bootstrap/
    └── app.php                       ← Framework bootstrapping & middleware
```

---

This document explains:
✅ What EcoShop is and why it was built
✅ Complete architecture with diagrams
✅ Database schema and relationships
✅ How authentication and authorization work
✅ Every API endpoint and what it does
✅ Real request/response examples
✅ How data flows through the system
✅ Component implementations with code
✅ Async event-driven order processing
✅ Testing patterns and strategy
✅ Docker containerization
✅ Why each architectural decision was made
