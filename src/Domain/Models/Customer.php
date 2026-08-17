<?php

namespace App\Domain\Models;

class Customer
{
    public function __construct(
        public ?int $id = null,
        public string $email = '',
        public ?string $name = null,
        public ?string $firstSeenAt = null,
        public ?string $lastSeenAt = null,
        public int $totalEvents = 0,
        public float $totalSpend = 0.0,
        public ?string $createdAt = null,
        public ?string $updatedAt = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            email: $data['email'] ?? '',
            name: $data['name'] ?? null,
            firstSeenAt: $data['first_seen_at'] ?? null,
            lastSeenAt: $data['last_seen_at'] ?? null,
            totalEvents: (int)($data['total_events'] ?? 0),
            totalSpend: (float)($data['total_spend'] ?? 0.0),
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'first_seen_at' => $this->firstSeenAt,
            'last_seen_at' => $this->lastSeenAt,
            'total_events' => $this->totalEvents,
            'total_spend' => $this->totalSpend,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
