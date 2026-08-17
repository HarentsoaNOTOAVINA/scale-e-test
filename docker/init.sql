CREATE DATABASE IF NOT EXISTS `scal_e_cdp` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `scal_e_cdp`;

-- 1. Customers Table
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

-- 2. Events Table
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

-- 3. API Keys Table
CREATE TABLE IF NOT EXISTS `api_keys` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `api_key` VARCHAR(64) NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_api_key` (`api_key`)
) ENGINE=InnoDB;

INSERT IGNORE INTO api_keys (name, api_key) VALUES ('Default Admin Key', 'scal-e-secret-key-2026');
