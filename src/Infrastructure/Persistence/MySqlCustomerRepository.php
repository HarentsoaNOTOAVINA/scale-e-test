<?php

namespace App\Infrastructure\Persistence;

use App\Core\Database;
use App\Domain\Models\Customer;
use App\Domain\Repositories\CustomerRepositoryInterface;
use PDO;

class MySqlCustomerRepository implements CustomerRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findById(int $id): ?Customer
    {
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();

        return $data ? Customer::fromArray($data) : null;
    }

    public function findByEmail(string $email): ?Customer
    {
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $data = $stmt->fetch();

        return $data ? Customer::fromArray($data) : null;
    }

    public function upsertByEmail(string $email, ?string $name, string $timestamp, float $spendDelta = 0.0): Customer
    {
        $sql = "INSERT INTO customers (email, name, first_seen_at, last_seen_at, total_events, total_spend)
                VALUES (:email, :name, :first_seen_at, :last_seen_at, 1, :total_spend)
                ON DUPLICATE KEY UPDATE
                    name = COALESCE(:name_update, name),
                    first_seen_at = LEAST(first_seen_at, :first_seen_at_update),
                    last_seen_at = GREATEST(last_seen_at, :last_seen_at_update),
                    total_events = total_events + 1,
                    total_spend = total_spend + :spend_delta";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'email' => $email,
            'name' => $name,
            'first_seen_at' => $timestamp,
            'last_seen_at' => $timestamp,
            'total_spend' => $spendDelta,
            'name_update' => $name,
            'first_seen_at_update' => $timestamp,
            'last_seen_at_update' => $timestamp,
            'spend_delta' => $spendDelta
        ]);

        return $this->findByEmail($email);
    }

    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare("SELECT * FROM customers ORDER BY id DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $customers = [];
        while ($row = $stmt->fetch()) {
            $customers[] = Customer::fromArray($row)->toArray();
        }

        return $customers;
    }

    public function count(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM customers");
        return (int)$stmt->fetchColumn();
    }

    public function getCustomerStats(int $customerId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_events,
                SUM(prop_amount) as total_spend,
                AVG(CASE WHEN prop_amount > 0 THEN prop_amount ELSE NULL END) as avg_order_value
            FROM events 
            WHERE customer_id = :customer_id
        ");
        $stmt->execute(['customer_id' => $customerId]);
        $stats = $stmt->fetch();

        $eventBreakdownStmt = $this->db->prepare("
            SELECT event_name, COUNT(*) as count 
            FROM events 
            WHERE customer_id = :customer_id 
            GROUP BY event_name
        ");
        $eventBreakdownStmt->execute(['customer_id' => $customerId]);
        $breakdown = $eventBreakdownStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return [
            'total_events' => (int)($stats['total_events'] ?? 0),
            'total_spend' => round((float)($stats['total_spend'] ?? 0.0), 2),
            'avg_order_value' => round((float)($stats['avg_order_value'] ?? 0.0), 2),
            'frequent_events' => $breakdown,
        ];
    }
}
