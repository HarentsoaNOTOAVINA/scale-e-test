<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

echo "=== Scal-e CDP Database Migration & Seeder ===" . PHP_EOL;

try {
    $config = require __DIR__ . '/../config/database.php';
    
    // Connect to MySQL server first without selecting DB to create DB if missing
    $pdoNoDb = new PDO(
        sprintf("mysql:host=%s;port=%s;charset=%s", $config['host'], $config['port'], $config['charset']),
        $config['username'],
        $config['password'],
        $config['options']
    );

    $dbName = $config['database'];
    $pdoNoDb->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "[OK] Database `$dbName` ensured." . PHP_EOL;

    $db = Database::getConnection();

    // 1. Customers Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `customers` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `email` VARCHAR(255) NOT NULL,
            `name` VARCHAR(255) NULL,
            `first_seen_at` DATETIME NOT NULL,
            `last_seen_at` DATETIME NOT NULL,
            `total_events` INT UNSIGNED DEFAULT 0,
            `total_spend` DECIMAL(12, 2) DEFAULT 0.00,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_customers_email` (`email`),
            INDEX `idx_customers_last_seen` (`last_seen_at`),
            INDEX `idx_customers_spend` (`total_spend`)
        ) ENGINE=InnoDB;
    ");
    echo "[OK] Table `customers` created/verified." . PHP_EOL;

    // 2. Events Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `events` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `customer_id` BIGINT UNSIGNED NOT NULL,
            `event_name` VARCHAR(100) NOT NULL,
            `properties` JSON NOT NULL,
            `timestamp` DATETIME NOT NULL,
            `idempotency_key` VARCHAR(64) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `prop_amount` DECIMAL(10,2) GENERATED ALWAYS AS (CAST(JSON_UNQUOTE(JSON_EXTRACT(`properties`, '$.amount')) AS DECIMAL(10,2))) VIRTUAL,
            `prop_product` VARCHAR(255) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(`properties`, '$.product'))) VIRTUAL,
            FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
            UNIQUE KEY `uk_events_idempotency` (`idempotency_key`),
            INDEX `idx_events_customer_ts` (`customer_id`, `timestamp` DESC),
            INDEX `idx_events_name_ts` (`event_name`, `timestamp`),
            INDEX `idx_events_amount` (`prop_amount`),
            INDEX `idx_events_product` (`prop_product`)
        ) ENGINE=InnoDB;
    ");
    echo "[OK] Table `events` created/verified." . PHP_EOL;

    // 3. API Keys Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `api_keys` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `api_key` VARCHAR(64) NOT NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_api_key` (`api_key`)
        ) ENGINE=InnoDB;
    ");
    echo "[OK] Table `api_keys` created/verified." . PHP_EOL;

    // Insert Default API key
    $appConfig = require __DIR__ . '/../config/app.php';
    $defaultKey = $appConfig['api_key'] ?? 'scal-e-secret-key-2026';
    $stmt = $db->prepare("INSERT IGNORE INTO api_keys (name, api_key) VALUES ('Default Admin Key', :key)");
    $stmt->execute(['key' => $defaultKey]);

    echo "[SUCCESS] Migration completed successfully." . PHP_EOL;

    // Seeding optional check
    if (isset($argv[1]) && $argv[1] === '--seed') {
        echo "--> Seeding demo data..." . PHP_EOL;
        require __DIR__ . '/seed.php';
    }

} catch (Exception $e) {
    echo "[ERROR] Migration failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
