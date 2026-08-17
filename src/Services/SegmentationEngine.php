<?php

namespace App\Services;

use App\Core\Database;
use InvalidArgumentException;
use PDO;

class SegmentationEngine
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function query(array $payload): array
    {
        $startTime = microtime(true);

        $conditions = $payload['conditions'] ?? [];
        if (!is_array($conditions) || empty($conditions)) {
            throw new InvalidArgumentException("At least one condition must be provided in 'conditions'.");
        }

        $logic = strtoupper($payload['logic'] ?? 'AND');
        if (!in_array($logic, ['AND', 'OR'], true)) {
            $logic = 'AND';
        }

        $params = [];
        $whereClauses = [];

        foreach ($conditions as $index => $condition) {
            $clause = $this->buildConditionClause($condition, $index, $params);
            $whereClauses[] = $clause;
        }

        $combinedWhere = implode(" $logic ", $whereClauses);

        $sql = "SELECT c.* 
                FROM customers c 
                WHERE $combinedWhere 
                ORDER BY c.total_spend DESC, c.last_seen_at DESC";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $paramKey => $paramValue) {
            $stmt->bindValue($paramKey, $paramValue);
        }
        $stmt->execute();

        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $executionTimeMs = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'status' => 'success',
            'meta' => [
                'total_matched' => count($customers),
                'logic' => $logic,
                'execution_time_ms' => $executionTimeMs,
            ],
            'data' => $customers
        ];
    }

    private function buildConditionClause(array $condition, int $index, array &$params): string
    {
        $eventName = $condition['event'] ?? null;
        $property = $condition['property'] ?? null;
        $operator = strtoupper($condition['operator'] ?? '=');
        $value = $condition['value'] ?? null;

        if (empty($eventName)) {
            throw new InvalidArgumentException("Condition #$index must specify an 'event'.");
        }

        $eventParam = ":event_$index";
        $params[$eventParam] = $eventName;

        // If no property specified, match event existence
        if (empty($property)) {
            return "EXISTS (SELECT 1 FROM events e_$index WHERE e_$index.customer_id = c.id AND e_$index.event_name = $eventParam)";
        }

        $valParam = ":val_$index";
        $opSql = $this->mapOperator($operator, $value, $valParam, $params);

        // Check if property maps to indexed virtual column
        $propField = match (strtolower($property)) {
            'amount' => "e_$index.prop_amount",
            'product' => "e_$index.prop_product",
            default => "JSON_UNQUOTE(JSON_EXTRACT(e_$index.properties, '$.$property'))"
        };

        // Numerical cast if comparing numbers on generic JSON field
        if (!in_array(strtolower($property), ['amount', 'product'], true) && is_numeric($value)) {
            $propField = "CAST(JSON_UNQUOTE(JSON_EXTRACT(e_$index.properties, '$.$property')) AS DECIMAL(12,2))";
        }

        return "EXISTS (
            SELECT 1 FROM events e_$index 
            WHERE e_$index.customer_id = c.id 
              AND e_$index.event_name = $eventParam 
              AND $propField $opSql
        )";
    }

    private function mapOperator(string $operator, mixed $value, string $paramName, array &$params): string
    {
        switch ($operator) {
            case '>':
            case '>=':
            case '<':
            case '<=':
            case '=':
            case '!=':
                $params[$paramName] = $value;
                return "$operator $paramName";

            case 'CONTAINS':
            case 'LIKE':
                $params[$paramName] = '%' . $value . '%';
                return "LIKE $paramName";

            case 'STARTS_WITH':
                $params[$paramName] = $value . '%';
                return "LIKE $paramName";

            case 'ENDS_WITH':
                $params[$paramName] = '%' . $value;
                return "LIKE $paramName";

            case 'IN':
                if (!is_array($value)) {
                    $value = array_map('trim', explode(',', (string)$value));
                }
                $inParams = [];
                foreach ($value as $k => $val) {
                    $pName = $paramName . "_in_$k";
                    $params[$pName] = $val;
                    $inParams[] = $pName;
                }
                return "IN (" . implode(', ', $inParams) . ")";

            default:
                $params[$paramName] = $value;
                return "= $paramName";
        }
    }
}
