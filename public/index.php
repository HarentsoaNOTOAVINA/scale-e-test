<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\SegmentController;

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-API-Key, X-Idempotency-Key");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$request = Request::createFromGlobals();
$router = new Router();

// 1. Ingestion API Endpoints
$router->post('/api/events', [EventController::class, 'ingest']);
$router->post('/api/v1/events', [EventController::class, 'ingest']);

// 2. Customer Profile Endpoints
$router->get('/api/customers/{id}', [CustomerController::class, 'getProfile']);
$router->get('/api/v1/customers/{id}', [CustomerController::class, 'getProfile']);
$router->get('/api/v1/customers', [CustomerController::class, 'listCustomers']);

// 3. Events List Endpoint
$router->get('/api/v1/events/recent', [CustomerController::class, 'listRecentEvents']);

// 4. Segmentation Engine Endpoints
$router->post('/api/segments/query', [SegmentController::class, 'query']);
$router->post('/api/v1/segments/query', [SegmentController::class, 'query']);

// 5. Dashboard UI Route
$router->get('/', function () {
    ob_start();
    include __DIR__ . '/../templates/dashboard.php';
    $html = ob_get_clean();
    return Response::html($html);
});

// Dispatch request
try {
    $response = $router->dispatch($request);
    $response->send();
} catch (Throwable $e) {
    $errResponse = Response::error("Server Error: " . $e->getMessage(), 500);
    $errResponse->send();
}
