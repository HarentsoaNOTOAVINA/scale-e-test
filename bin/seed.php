<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Persistence\MySqlCustomerRepository;
use App\Infrastructure\Persistence\MySqlEventRepository;
use App\Services\IngestionService;

$ingestion = new IngestionService(
    new MySqlCustomerRepository(),
    new MySqlEventRepository()
);

$demoEvents = [
    [
        'customer' => ['email' => 'john.doe@example.com', 'name' => 'John Doe'],
        'event' => 'purchase',
        'properties' => ['amount' => 120.50, 'product' => 'Shoes', 'category' => 'Sportswear'],
        'timestamp' => '2026-04-10T12:00:00Z',
    ],
    [
        'customer' => ['email' => 'john.doe@example.com', 'name' => 'John Doe'],
        'event' => 'purchase',
        'properties' => ['amount' => 250.00, 'product' => 'Leather Jacket', 'category' => 'Apparel'],
        'timestamp' => '2026-05-15T14:30:00Z',
    ],
    [
        'customer' => ['email' => 'alice.smith@example.com', 'name' => 'Alice Smith'],
        'event' => 'purchase',
        'properties' => ['amount' => 85.00, 'product' => 'Running Shoes', 'category' => 'Sportswear'],
        'timestamp' => '2026-06-01T09:15:00Z',
    ],
    [
        'customer' => ['email' => 'alice.smith@example.com', 'name' => 'Alice Smith'],
        'event' => 'page_view',
        'properties' => ['url' => '/catalog/shoes', 'duration' => 45],
        'timestamp' => '2026-06-01T09:10:00Z',
    ],
    [
        'customer' => ['email' => 'bob.wilson@example.com', 'name' => 'Bob Wilson'],
        'event' => 'signup',
        'properties' => ['channel' => 'google_ads'],
        'timestamp' => '2026-07-20T18:00:00Z',
    ],
    [
        'customer' => ['email' => 'bob.wilson@example.com', 'name' => 'Bob Wilson'],
        'event' => 'purchase',
        'properties' => ['amount' => 49.99, 'product' => 'Smart Watch Band', 'category' => 'Accessories'],
        'timestamp' => '2026-08-01T11:45:00Z',
    ],
    [
        'customer' => ['email' => 'charlie.brown@example.com', 'name' => 'Charlie Brown'],
        'event' => 'purchase',
        'properties' => ['amount' => 310.00, 'product' => 'Winter Boots', 'category' => 'Footwear'],
        'timestamp' => '2026-08-10T16:20:00Z',
    ],
    [
        'customer' => ['email' => 'charlie.brown@example.com', 'name' => 'Charlie Brown'],
        'event' => 'page_view',
        'properties' => ['url' => '/checkout', 'duration' => 120],
        'timestamp' => '2026-08-10T16:18:00Z',
    ],
];

$count = 0;
foreach ($demoEvents as $evt) {
    try {
        $ingestion->ingest($evt);
        $count++;
    } catch (Exception $e) {
        echo "Error seeding event: " . $e->getMessage() . PHP_EOL;
    }
}

echo "[SUCCESS] Seeded $count demo events successfully." . PHP_EOL;
