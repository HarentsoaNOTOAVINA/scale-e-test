<?php

namespace App\Domain\Repositories;

use App\Domain\Models\Customer;

interface CustomerRepositoryInterface
{
    public function findById(int $id): ?Customer;
    public function findByEmail(string $email): ?Customer;
    public function upsertByEmail(string $email, ?string $name, string $timestamp, float $spendDelta = 0.0): Customer;
    public function getAll(int $limit = 50, int $offset = 0): array;
    public function count(): int;
    public function getCustomerStats(int $customerId): array;
}
