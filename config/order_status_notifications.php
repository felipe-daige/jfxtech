<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Webhook destination
    |--------------------------------------------------------------------------
    |
    | n8n will listen on the URL below. Keeping the value in config allows
    | switching targets without touching code if automation endpoints move.
    |
    */
    'webhook_url' => env('ORDER_STATUS_NOTIFICATION_WEBHOOK', ''),

    /*
    |--------------------------------------------------------------------------
    | Request timeout
    |--------------------------------------------------------------------------
    |
    | Use a short timeout to avoid blocking the admin workflow for too long.
    |
    */
    'timeout' => env('ORDER_STATUS_NOTIFICATION_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | Enabled flag
    |--------------------------------------------------------------------------
    |
    | Toggle in configuration to disable notifications without touching code.
    |
    */
    'enabled' => env('ORDER_STATUS_NOTIFICATION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Header authentication (Header Auth no n8n)
    |--------------------------------------------------------------------------
    |
    | Se o webhook n8n exigir Header Auth, configure o nome e valor do header.
    | Exemplo: AUTH_HEADER=Authorization, AUTH_VALUE=Bearer <token>
    | Deixe em branco para não enviar autenticação.
    |
    */
    'auth_user' => env('ORDER_STATUS_NOTIFICATION_WEBHOOK_AUTH_USER', ''),
    'auth_pass' => env('ORDER_STATUS_NOTIFICATION_WEBHOOK_AUTH_PASS', ''),
];
