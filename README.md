# README.md

## Order Management API (Laravel 11)

Multi-tenant Order Management API with asynchronous invoice generation.

---

### Stack

* Laravel 11 (PHP 8.3)
* MySQL 8
* Sanctum (personal access tokens)
* Queue driver: **database**
* PHPUnit (SQLite in-memory for tests)

---

### Endpoints

All endpoints require `Authorization: Bearer <token>`.

| Method   | Endpoint                   | Description                                                  |
| -------- | -------------------------- | ------------------------------------------------------------ |
| **POST** | `/api/orders`              | Create order with items; enqueues async invoice job.         |
| **GET**  | `/api/orders/{id}`         | Retrieve single order and its items (same tenant).           |
| **GET**  | `/api/clients/{id}/orders` | List client orders (pagination, same tenant).                |
| **GET**  | `/api/orders/{id}/invoice` | Get persisted invoice; returns **404** if not generated yet. |

---

### Data Model

* `clients` → id, name, email
* `users` → id, client_id, name, email, password
* `orders` → id, client_id, status, total_amount, timestamps
* `order_items` → id, order_id, name, quantity, unit_price, line_total
* `invoices` → id, order_id [unique], invoice_number [unique], status, payload JSON

---

### Multi-tenancy

* Users belong to a client (`users.client_id`).
* All reads/writes are scoped by `client_id`.
* Cross-tenant access returns **404**, preventing IDOR issues.

---

### Setup (Laragon)

```bash
# 1) Clone & install
git clone https://github.com/mpulido887/order-mgmt.git order-mgmt
cd order-mgmt
composer install
cp .env.example .env
php artisan key:generate

# 2) MySQL: create ONE database for development
CREATE DATABASE order_mgmt CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

# 3) Verify .env DB settings
# .env defaults to DB_DATABASE=order_mgmt (Laragon: root / no password)
# Adjust DB_USERNAME or DB_PASSWORD only if needed.

# 4) Run migrations & seed demo data
php artisan migrate --seed
```

---

### Seeder Demo Tokens

```
== DEMO TOKENS ==
Client A / usera@example.com / pass: password
Bearer: <TOKEN_A>

Client B / userb@example.com / pass: password
Bearer: <TOKEN_B>
```

---

### Queue Worker

```bash
php artisan queue:work --queue=invoices --tries=3 --backoff=5
```

---

### Tests

**Zero setup:** tests use **SQLite in-memory** — no database creation required.

```bash
php artisan test
```

Tests automatically run with:

* `QUEUE_CONNECTION=sync`
* `CACHE_DRIVER=array`
* `SESSION_DRIVER=array`

Coverage includes:

* Feature: POST/GET + tenant isolation + invoice async
* Unit: OrderService totals, Job idempotency

---

### Postman Collection

A ready-to-use **Postman collection** is included in the project root:
 `OrderMgmt.postman_collection.json`

This collection automatically stores variables (`last_order_id`, `client_id`) between requests, enabling a full API flow test.

#### How to use

1. Open **Postman → Import → File** and select the JSON file in the project root.
2. In the **Variables** tab, fill in:

   * `base_url` → `http://order-mgmt.test`
   * `token` → your seeder token (without “Bearer”)
3. Execute the requests in order:

   1. **Create Order**
   2. **Get Order (last created)**
   3. **List Orders by Client**
   4. **Get Invoice**

---

### Design Notes

* Controllers are thin; orchestration is handled in `CreateOrderService`.
* Jobs dispatched **after commit** to ensure transactional consistency.
* Invoices are idempotent using `updateOrCreate`.
* Monetary values use `DECIMAL(12,2)` and totals are calculated server-side.
* Logs include `order_id`, `client_id`, and `job_id`.

---

### Troubleshooting

| Issue                        | Fix                                                    |
| ---------------------------- | ------------------------------------------------------ |
| Invoice endpoint returns 404 | Wait for queue worker to process job.                  |
| “dispatch undefined” on Job  | Ensure `GenerateInvoiceJob` uses `Dispatchable` trait. |
| Need DB reset                | Run `php artisan migrate:fresh --seed` ( wipes data). |

---

### composer.json — Optional Scripts

Add these shortcuts for convenience:

```json
{
  "scripts": {
    "seed": "php artisan migrate:fresh --seed",
    "queue": "php artisan queue:work --queue=invoices --tries=3 --backoff=5",
    "test": "php artisan test"
  }
}
```
