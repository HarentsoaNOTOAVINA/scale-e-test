<?php

namespace App\Http\Middleware;

use App\Core\Request;
use App\Core\Response;

class AuthMiddleware
{
    public static function checkApiKey(Request $request, ?string $requiredKey = null): ?Response
    {
        if ($requiredKey === null) {
            $config = require __DIR__ . '/../../../config/app.php';
            $requiredKey = $config['api_key'] ?? null;
        }

        if (empty($requiredKey)) {
            return null; // Auth disabled if no required key set
        }

        $providedKey = $request->getHeader('X-API-Key') ?? $request->getQuery('api_key');

        if ($providedKey !== $requiredKey) {
            return Response::error("Unauthorized: Invalid or missing X-API-Key header.", 401);
        }

        return null;
    }
}
