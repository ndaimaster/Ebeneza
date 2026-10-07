<?php
require_once __DIR__ . '/env.php';

return [
    'env' => env('APP_ENV', 'development'),
    'url' => rtrim(env('APP_URL', 'http://localhost/Ebeneza'), '/'),
    'is_production' => env('APP_ENV', 'development') === 'production',
];
