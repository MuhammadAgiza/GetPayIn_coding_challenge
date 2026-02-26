# Flash-Sale Checkout API (Laravel 12)

This project implements a robust, high-concurrency flash-sale checkout API in Laravel 12 and MySQL. It ensures **no overselling**, supports **temporary holds**, **idempotent payment webhooks**, and **accurate stock reporting** under heavy load.

---

## Features

- **Product Endpoint:**  
  Fast, cached product info with real-time available stock.

- **Create Hold:**  
  Reserve stock for ~2 minutes. Holds reduce available stock immediately and auto-expire in the background.

- **Create Order:**  
  Orders can only be created from valid, unexpired holds. Each hold can be used once.

- **Payment Webhook:**  
  Idempotent endpoint for payment provider callbacks. Handles duplicate and out-of-order webhooks safely.

- **No Overselling:**  
  All stock changes are transactional and race-condition safe.

- **Background Hold Expiry:**  
  Expired holds are released automatically, restoring stock.

- **Caching:**  
  Product reads are cached for speed, with automatic invalidation on stock changes.

---

## Assumptions & Invariants

- **Stock is never oversold:**  
  All stock decrements use DB transactions and row-level locks.

- **Holds expire after 2 minutes:**  
  Expired holds are released by a scheduled background job.

- **Each hold can be used once:**  
  Orders reference a single hold, which is marked as used.

- **Webhook idempotency:**  
  Each payment webhook is processed once per unique idempotency key.

- **Webhook out-of-order:**  
  If a webhook arrives before the order exists, it is safely ignored (422 returned).

- **No N+1 queries:**  
  All endpoints are optimized to avoid N+1 DB queries.

---

## How to Run

### Prerequisites

- PHP
- Composer
- MySQL
- Redis (for cache)

### Setup

1. **Clone the repo:**
   ```sh
   git clone https://github.com/MuhammadAgiza/Flash_Sale_Checkout_API
   cd Flash_Sale_Checkout_API
   ```

2. **Install dependencies:**
   ```sh
   composer install
   ```

3. **Configure environment:**
   - Copy `.env.example` to `.env` and set DB credentials.

4. **Run migrations:**
   ```sh
   php artisan migrate
   ```

5. **Run the scheduler (for hold expiry):**
   ```sh
   php artisan schedule:work
   ```

6. **Start the server:**
   ```sh
   php artisan serve
   ```

---

## API Endpoints

- `GET /api/products/{id}`  
  Get product info and available stock.

- `POST /api/holds`  
  Reserve stock.  
  **Body:** `{ "product_id": 1, "qty": 2 }`

- `POST /api/orders`  
  Create order from a hold.  
  **Body:** `{ "hold_id": 123 }`

- `POST /api/payments/webhook`  
  Payment provider callback.  
  **Body:** `{ "order_id": 456, "reference": "unique-key", "status": "success"|"failed" }`

---

## Logs & Metrics

- **Logs:**  
  All API requests, payment webhook deduplication, and contention events are logged to `storage/logs/laravel.log`.

- **Metrics:**  
  Key events (holds created, orders placed, webhooks deduped) are logged for analysis.

---

## Automated Tests

- **No overselling:**  
  Parallel hold attempts at stock boundary.

- **Hold expiry:**  
  Expired holds restore availability.

- **Webhook idempotency:**  
  Repeated webhooks with same key are safe.

- **Webhook before order:**  
  Webhook arriving before order creation is handled gracefully.

Run all tests with:

```sh
php artisan test
```
