<?php

return [
    'security' => [
        'web_absolute_lifetime_minutes' => (int) env('SENTAI_WEB_ABSOLUTE_LIFETIME_MINUTES', 480),
        'mobile_access_ttl_minutes' => (int) env('SENTAI_MOBILE_ACCESS_TTL_MINUTES', 15),
        'mobile_refresh_ttl_days' => (int) env('SENTAI_MOBILE_REFRESH_TTL_DAYS', 30),
    ],
];
