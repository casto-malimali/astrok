<?php

return [
    'paths' => ['api/*'],               // add 'sanctum/csrf-cookie' ONLY if using cookie SPA
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    // Replace with your exact frontend domains (no trailing slash)
    'allowed_origins' => [
        'https://127.0.0.1:8000',
        // 'https://app.your-frontend.example',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
    'exposed_headers' => [],
    'max_age' => 3600,
    'supports_credentials' => false,    // keep false for token APIs
];
