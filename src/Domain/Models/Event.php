<?php

namespace App\Domain\Models;

class Event
{
    public function __construct(
        public ?int $id = null,
        public int $customerId = 0,
        public string $eventName = '',
        public array $properties = [],
        public string $timestamp = '',
        public ?string $idempotencyKey = null,
        public ?string $createdAt = null
    ) {}

    public static function fromArray(array $data): self
    {
        $props = $data['properties'] ?? [];
        if (is_string($props)) {
            $props = json_decode($props, true) ?? [];
        }

        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            customerId: (int)($data['customer_id'] ?? 0),
            eventName: $data['event_name'] ?? '',
            properties: $props,
            timestamp: $data['timestamp'] ?? date('Y-m-d H:i:s'),
            idempotencyKey: $data['idempotency_key'] ?? null,
            createdAt: $data['created_at'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customerId,
            'event_name' => $this->eventName,
            'properties' => $this->properties,
            'timestamp' => $this->timestamp,
            'idempotency_key' => $this->idempotencyKey,
            'created_at' => $this->createdAt,
        ];
    }
}
