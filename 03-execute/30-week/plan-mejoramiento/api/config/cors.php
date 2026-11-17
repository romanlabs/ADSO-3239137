<?php

/*
 * Contract §4: only the frontend origin set in CORS_ORIGIN may call the API from a browser.
 */
return [
    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [env('CORS_ORIGIN', 'http://localhost:5173')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Authorization', 'Content-Type', 'Accept'],

    'exposed_headers' => [],

    'max_age' => 600,

    'supports_credentials' => false,
];
