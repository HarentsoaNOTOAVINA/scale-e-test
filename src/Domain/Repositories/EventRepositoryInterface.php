<?php

namespace App\Domain\Repositories;

use App\Domain\Models\Event;

interface EventRepositoryInterface
{
    public function create(Event $event): Event;
    public function findByIdempotencyKey(string $key): ?Event;
    public function getRecentByCustomerId(int $customerId, int $limit = 10): array;
    public function getAllRecent(int $limit = 50): array;
}
