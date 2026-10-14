# Scal-e Technical Test - Senior Web Developer: Simplified Customer Data Platform (CDP)

A high-performance, lightweight, scalable **Customer Data Platform (CDP)** backend written in **PHP 8.2 (Pure OOP / Clean Architecture)** and **MySQL 8.0**, featuring real-time event collection, customer profile aggregation, an AST-based segmentation engine, and an interactive presentation dashboard.

---

## 🚀 Key Features

1. **Event Ingestion API (`POST /api/v1/events`)**:
   - Collects customer events, automatically creates/upserts customer profiles by email.
   - Idempotency support (`X-Idempotency-Key` header) to avoid duplicate event processing.
   - Spend delta auto-aggregation and timestamp tracking.

2. **Customer Profile & Aggregation (`GET /api/v1/customers/{id}`)**:
   - Retrieves customer attributes, total spend, total events count, average order value, event distribution breakdown, and the stream of recent 10 events.

3. **Segmentation Engine (`POST /api/v1/segments/query`)**:
   - Compiles complex condition sets (AND/OR logic) into parameterized SQL queries with `EXISTS` subqueries.
   - Leverages **MySQL 8.0 Virtual Generated Columns** and B-Tree indexes (`prop_amount`, `prop_product`) to eliminate Full Table Scans and achieve sub-5ms query times.

4. **Interactive Dashboard UI**:
   - Built with Vanilla HTML5/CSS3 (Glassmorphism Dark Theme) and JavaScript.
   - Live Event Ingestion simulator, Customer Directory Inspector, Visual Segment Builder, and Real-time Activity Log.

5. **Docker & Multi-Container Ready**:
   - Pre-configured `docker-compose.yml` (PHP 8.2-FPM, Nginx, MySQL 8.0, Redis).

---

## 🛠️ Architecture & Design Decisions

### 1. Pure OOP without Heavy Framework Overhead
Per the assessment guidelines (*"If possible, use your own OOP logic and avoid direct usage of prepackaged frameworks"*), the application is built using standard **PHP 8.2**, PSR-4 autoloading, clean Separation of Concerns (Domain, Infrastructure, Services, Http Controllers), and a lightweight custom Router.

### 2. Database Schema Optimization (MySQL 8.0)
- **JSON + Virtual Columns**: E-commerce event properties are dynamic by nature. Instead of an EAV (Entity-Attribute-Value) pattern requiring expensive SQL JOINs, we store `properties` as JSON.
- **Indexed Virtual Columns**: For frequently queried attributes (e.g., `amount`, `product`), we define MySQL Virtual Generated Columns:
  ```sql
  `prop_amount` DECIMAL(10,2) GENERATED ALWAYS AS (CAST(JSON_UNQUOTE(JSON_EXTRACT(`properties`, '$.amount')) AS DECIMAL(10,2))) VIRTUAL,
  INDEX `idx_events_amount` (`prop_amount`)
  ```
  This allows B-Tree index lookup directly on JSON payload values.

### 3. Segmentation AST Compiler
When a segmentation payload is received:
```json
{
  "logic": "AND",
  "conditions": [
    { "event": "purchase", "property": "amount", "operator": ">", "value": 100 }
  ]
}
```
The `SegmentationEngine` parses the AST, maps fields to indexed virtual columns or JSON extraction functions, and generates a safe PDO parameterized query using `EXISTS` subqueries.

---

## 🚦 Quick Start Guide

### 1. Environment Configuration

The application uses an environment-based configuration loaded via a lightweight built-in `Env` parser (or via Docker Compose environment variables).

1. Copy the sample environment file:
   ```bash
   cp .env.example .env
   # On Windows PowerShell:
   copy .env.example .env
   ```

2. Review `.env` and adjust if needed:
   * **`DB_HOST`**: Set to `db` if running with Docker, or `127.0.0.1` if running locally on your host machine.
   * **`DB_USER`** / **`DB_PASS`**: Database credentials (`root` / `root_password`).
   * **`API_KEY`**: Development API key (defaults to `scal-e-secret-key-2026-dev`).

---

### Option A: Running with Docker (Recommended)

#### 1. Launch the Application
Start the containers in the background (Docker Compose automatically merges `docker-compose.yml` and `docker-compose.override.yml`):
```bash
docker-compose up -d
```
*Web server will be available at **`http://localhost:8080`**.*

#### 2. Run Database Migrations
Create the database tables, schema indexes, and virtual generated columns:
```bash
docker exec scale_cdp_app php bin/migrate.php
```

#### 3. Seed Demo Data
Populate the database with initial demo customers and events:
```bash
docker exec scale_cdp_app php bin/seed.php
```
*(Alternatively, run migration and seed in one step: `docker exec scale_cdp_app php bin/migrate.php --seed`)*

#### 4. Access the App & Dashboard
Open your browser at:
```text
http://localhost:8080
```

---

### Option B: Running Locally (PHP CLI & Local MySQL)

#### 1. Prerequisites
* **PHP 8.2+** with `pdo_mysql` and `json` extensions.
* **MySQL 8.0+** running locally.
* **Composer**.

#### 2. Install Dependencies
```bash
composer install
```

#### 3. Configure Database
Ensure `.env` points to your local MySQL instance:
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=scal_e_cdp
DB_USER=root
DB_PASS=root_password
```

#### 4. Run Migrations & Seed
Execute the migration and seeder script:
```bash
# Run migration only:
php bin/migrate.php

# Seed sample customers and events:
php bin/seed.php

# Or run both simultaneously:
php bin/migrate.php --seed
```

#### 5. Launch the PHP Built-in Server
```bash
php -S localhost:8000 -t public
```

#### 6. Access the App
Open your browser at:
```text
http://localhost:8000
```

---

## 📡 API Reference

### 1. Ingest Event
- **Endpoint**: `POST /api/v1/events` (or `/api/events`)
- **Headers**: `Content-Type: application/json`, `X-API-Key: scal-e-secret-key-2026-dev` (or value of `API_KEY` in `.env`)
- **Request Body**:
```json
{
  "customer": {
    "email": "john.doe@example.com",
    "name": "John Doe"
  },
  "event": "purchase",
  "properties": {
    "amount": 120.50,
    "product": "Shoes"
  },
  "timestamp": "2026-08-17T12:00:00Z"
}
```

### 2. Get Customer Profile
- **Endpoint**: `GET /api/v1/customers/{id}` (or by email `GET /api/v1/customers/john.doe@example.com`)
- **Headers**: `X-API-Key: scal-e-secret-key-2026-dev`
- **Response**:
```json
{
  "status": "success",
  "data": {
    "customer": {
      "id": 1,
      "email": "john.doe@example.com",
      "name": "John Doe",
      "first_seen_at": "2026-04-10 12:00:00",
      "last_seen_at": "2026-05-15 14:30:00",
      "total_events": 2,
      "total_spend": 370.5
    },
    "statistics": {
      "total_events": 2,
      "total_spend": 370.5,
      "avg_order_value": 185.25,
      "frequent_events": {
        "purchase": 2
      }
    },
    "recent_events": [ ... ]
  }
}
```

### 3. Run Segment Query
- **Endpoint**: `POST /api/v1/segments/query`
- **Headers**: `Content-Type: application/json`, `X-API-Key: scal-e-secret-key-2026-dev`
- **Request Body**:
```json
{
  "logic": "AND",
  "conditions": [
    {
      "event": "purchase",
      "property": "amount",
      "operator": ">",
      "value": 100
    }
  ]
}
```

---

## 🧪 Unit Tests

Run the PHPUnit test suite:

* **Inside Docker**:
  ```bash
  docker exec scale_cdp_app vendor/bin/phpunit
  ```

* **Locally (with local PHP CLI)**:
  ```bash
  php vendor/bin/phpunit
  ```

---

## 📑 Assumptions & Potential Future Improvements

1. **Asynchronous Queue Worker**: High-volume events (>10,000 req/sec) should be pushed to Redis/RabbitMQ queue and processed by CLI workers (`php bin/worker.php`).
2. **Caching Strategy**: Redis caching for customer statistics with TTL invalidation on event ingestion.
3. **Partitioning**: Range partitioning on `events.timestamp` by month/year for horizontal table scaling.
