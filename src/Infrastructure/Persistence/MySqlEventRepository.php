<?php

namespace App\Infrastructure\Persistence;

use App\Core\Database;
use App\Domain\Models\Event;
use App\Domain\Repositories\EventRepositoryInterface;
use PDO;

class MySqlEventRepository implements EventRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function create(Event $event): Event
    {
        $sql = "INSERT INTO events (customer_id, event_name, properties, timestamp, idempotency_key)
                VALUES (:customer_id, :event_name, :properties, :timestamp, :idempotency_key)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'customer_id' => $event->customerId,
            'event_name' => $event->eventName,
            'properties' => json_encode($event->properties, JSON_UNESCAPED_UNICODE),
            'timestamp' => $event->timestamp,
            'idempotency_key' => $event->idempotencyKey,
        ]);

        $event->id = (int)$this->db->lastInsertId();
        return $event;
    }

    public function findByIdempotencyKey(string $key): ?Event
    {
        $stmt = $this->db->prepare("SELECT * FROM events WHERE idempotency_key = :key LIMIT 1");
        $stmt->execute(['key' => $key]);
        $data = $stmt->fetch();

        return $data ? Event::fromArray($data) : null;
    }

    public function getRecentByCustomerId(int $customerId, int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM events 
            WHERE customer_id = :customer_id 
            ORDER BY timestamp DESC, id DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $events = [];
        while ($row = $stmt->fetch()) {
            $events[] = Event::fromArray($row)->toArray();
        }

        return $events;
    }

    public function getAllRecent(int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT e.*, c.email as customer_email, c.name as customer_name
            FROM events e
            JOIN customers c ON c.id = e.customer_id
            ORDER BY e.timestamp DESC, e.id DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $events = [];
        while ($row = $stmt->fetch()) {
            $eventArr = Event::fromArray($row)->toArray();
            $eventArr['customer_email'] = $row['customer_email'];
            $eventArr['customer_name'] = $row['customer_name'];
            $events[] = $eventArr;
        }

        return $events;
    }
}
