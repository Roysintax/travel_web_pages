<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Midtrans Server Key
    |--------------------------------------------------------------------------
    |
    | Used for backend-to-backend authentication with Midtrans API.
    | Strictly kept on the backend; never exposed to browser or client.
    |
    */
    'server_key' => env('MIDTRANS_SERVER_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Midtrans Client Key
    |--------------------------------------------------------------------------
    |
    | Used on the frontend for Snap JavaScript SDK integration.
    |
    */
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'merchant_id' => env('MIDTRANS_MERCHANT_ID', 'G764706186'),

    /*
    |--------------------------------------------------------------------------
    | Environment Mode
    |--------------------------------------------------------------------------
    |
    | Set to true for Production or false for Sandbox testing.
    |
    */
    'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
    'simulation_enabled' => (bool) env('MIDTRANS_SIMULATION_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Advanced Snap Options
    |--------------------------------------------------------------------------
    |
    | Sanitization and 3D Secure verification options for credit cards.
    |
    */
    'is_sanitized' => true,
    'is_3ds' => true,

    /*
    |--------------------------------------------------------------------------
    | Bank Indonesia SNAP (BI-SNAP / Open API) Credentials (Optional)
    |--------------------------------------------------------------------------
    |
    | For BI-SNAP Open API integrations if migrating from standard Snap.
    |
    */
    'client_id' => env('MIDTRANS_CLIENT_ID'),
    'client_secret' => env('MIDTRANS_CLIENT_SECRET'),
    'public_key' => env('MIDTRANS_PUBLIC_KEY'),

];
