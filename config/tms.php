<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TMS API — Torre de Controle
    |--------------------------------------------------------------------------
    */

    'token' => env('API_TMS_TOKEN'),
    'subscription_id' => env('API_TMS_SUBSCRIPTION'),
    'tenant_id' => env('API_TMS_TENANT'),
    'report_endpoint' => env('TMS_REPORT_ENDPOINT'),

    /** Timeout máximo da requisição em segundos */
    'timeout' => 120,

];
