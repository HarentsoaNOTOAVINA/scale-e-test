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

### Option A: Running with Docker (Recommended)

1. Clone the repository and navigate to project root:
   ```bash
   cd scale-test
   ```

2. Start the services (Docker Compose will automatically combine `docker-compose.yml` and `docker-compose.override.yml` for local development):
   ```bash
   docker-compose up -d
   ```

3. Open your browser and access the Dashboard:
   ```text
   http://localhost:8080
   ```

> **Note on `docker-compose.override.yml`**: The file [`docker-compose.override.yml`](file:///e:/Projets/Test/scale-test/docker-compose.override.yml) is automatically loaded by Docker Compose to override settings for local development (e.g. enabling `APP_DEBUG=true`, binding local volume mounts, and setting dev API keys).

---

### Option B: Running Locally with PHP CLI & MySQL

1. Ensure PHP 8.2+ and MySQL 8.0+ are running.

2. Install Composer dependencies & dump autoloader:
   ```bash
   composer install
   ```

3. Configure environment or edit `config/database.php` with your MySQL credentials.

4. Run Database Migrations and Seed demo data:
   ```bash
   php bin/migrate.php --seed
   ```

5. Start the local PHP development server:
   ```bash
   php -S localhost:8000 -t public
   ```

6. Open your browser at `http://localhost:8000`.

---

## 📡 API Reference

### 1. Ingest Event
- **Endpoint**: `POST /api/v1/events` (or `/api/events`)
- **Headers**: `Content-Type: application/json`, `X-API-Key: scal-e-secret-key-2026`
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
```bash
./vendor/bin/phpunit
```

---

## 📑 Assumptions & Potential Future Improvements

1. **Asynchronous Queue Worker**: High-volume events (>10,000 req/sec) should be pushed to Redis/RabbitMQ queue and processed by CLI workers (`php bin/worker.php`).
2. **Caching Strategy**: Redis caching for customer statistics with TTL invalidation on event ingestion.
3. **Partitioning**: Range partitioning on `events.timestamp` by month/year for horizontal table scaling.
