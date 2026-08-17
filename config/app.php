<?php

return [
    'name' => 'Scal-e CDP Backend',
    'env' => $_ENV['APP_ENV'] ?? 'development',
    'debug' => (bool)($_ENV['APP_DEBUG'] ?? true),
    'api_key' => $_ENV['API_KEY'] ?? 'scal-e-secret-key-2026',
    'timezone' => 'UTC',
];
