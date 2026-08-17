<?php

namespace App\Services;

use App\Domain\Models\Event;
use App\Domain\Repositories\CustomerRepositoryInterface;
use App\Domain\Repositories\EventRepositoryInterface;
use InvalidArgumentException;

class IngestionService
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepo,
        private EventRepositoryInterface $eventRepo
    ) {}

    public function ingest(array $payload, ?string $idempotencyKey = null): array
    {
        $this->validatePayload($payload);

        $customerData = $payload['customer'];
        $eventName = trim($payload['event']);
        $properties = $payload['properties'] ?? [];
        $timestamp = isset($payload['timestamp']) ? date('Y-m-d H:i:s', strtotime($payload['timestamp'])) : date('Y-m-d H:i:s');

        // Check idempotency if key provided
        if (!empty($idempotencyKey)) {
            $existing = $this->eventRepo->findByIdempotencyKey($idempotencyKey);
            if ($existing !== null) {
                return [
                    'status' => 'duplicate',
                    'message' => 'Event already processed (idempotent)',
                    'data' => [
                        'event_id' => $existing->id,
                        'customer_id' => $existing->customerId,
                    ]
                ];
            }
        }

        // Determine spend delta
        $spendDelta = 0.0;
        if (isset($properties['amount']) && is_numeric($properties['amount'])) {
            $spendDelta = (float)$properties['amount'];
        }

        // 1. Upsert customer profile
        $customer = $this->customerRepo->upsertByEmail(
            email: trim($customerData['email']),
            name: !empty($customerData['name']) ? trim($customerData['name']) : null,
            timestamp: $timestamp,
            spendDelta: $spendDelta
        );

        // 2. Insert event
        $event = new Event(
            customerId: $customer->id,
            eventName: $eventName,
            properties: $properties,
            timestamp: $timestamp,
            idempotencyKey: $idempotencyKey
        );

        $createdEvent = $this->eventRepo->create($event);

        return [
            'status' => 'success',
            'message' => 'Event ingested successfully',
            'data' => [
                'event_id' => $createdEvent->id,
                'customer_id' => $customer->id,
                'customer_email' => $customer->email,
            ]
        ];
    }

    private function validatePayload(array $payload): void
    {
        if (empty($payload['customer']) || !is_array($payload['customer'])) {
            throw new InvalidArgumentException("Missing or invalid 'customer' object.");
        }

        $email = $payload['customer']['email'] ?? '';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("A valid customer email is required.");
        }

        if (empty($payload['event']) || !is_string($payload['event'])) {
            throw new InvalidArgumentException("Field 'event' is required and must be a string.");
        }

        if (isset($payload['timestamp']) && strtotime($payload['timestamp']) === false) {
            throw new InvalidArgumentException("Field 'timestamp' must be a valid ISO 8601 or DATETIME string.");
        }
    }
}
