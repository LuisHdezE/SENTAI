<?php

use Sentai\Modules\Identity\Infrastructure\Persistence\Eloquent\UserRecord;

return [
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => UserRecord::class,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];
