<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Env;

// Load .env variables if available
Env::load(__DIR__ . '/../.env');
