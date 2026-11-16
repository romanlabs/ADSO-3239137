<?php

/*
 * Settings of the laptop lending adapters. Every value comes from the environment (constitution, article IX);
 * secrets have no default on purpose.
 */
return [
    'jwt_secret' => env('JWT_SECRET'),

    'photos_dir' => env('PHOTOS_DIR', storage_path('app/photos')),

    'clock_offset_days' => (int) env('CLOCK_OFFSET_DAYS', 0),

    'admin_username' => env('ADMIN_USERNAME'),

    'admin_password' => env('ADMIN_PASSWORD'),

    'cors_origin' => env('CORS_ORIGIN', 'http://localhost:5173'),
];
