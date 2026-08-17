<?php

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Http\Middleware\AuthMiddleware;
use App\Infrastructure\Persistence\MySqlCustomerRepository;
use App\Infrastructure\Persistence\MySqlEventRepository;
use App\Services\IngestionService;
use InvalidArgumentException;
use Exception;

class EventController
{
    private IngestionService $ingestionService;

    public function __construct(?IngestionService $ingestionService = null)
    {
        $this->ingestionService = $ingestionService ?? new IngestionService(
            new MySqlCustomerRepository(),
            new MySqlEventRepository()
        );
    }

    public function ingest(Request $request): Response
    {
        if ($authError = AuthMiddleware::checkApiKey($request)) {
            return $authError;
        }

        $body = $request->getBody();
        if (!is_array($body)) {
            return Response::error("Invalid JSON body payload.", 400);
        }

        $idempotencyKey = $request->getHeader('X-Idempotency-Key');

        try {
            $result = $this->ingestionService->ingest($body, $idempotencyKey);
            $statusCode = ($result['status'] === 'duplicate') ? 200 : 201;

            return Response::json($result, $statusCode);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        } catch (Exception $e) {
            return Response::error("Failed to ingest event: " . $e->getMessage(), 500);
        }
    }
}
