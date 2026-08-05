<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'bigcore' => [
        'endpoint' => env('TMS_API_ENDPOINT', 'https://api-elog.bigcore.com.br/api/ControlTower'),
        'token' => env('API_TMS_TOKEN'),
        'tenant' => env('API_TMS_TENANT'),
        'subscription' => env('API_TMS_SUBSCRIPTION'),
        'sync_key' => env('API_TMS_TOKEN'),
    ],

    'vfleets' => [
        'token_url' => 'https://idp.vfleets.com.br/realms/integration/protocol/openid-connect/token',
        'api_url' => 'https://api.vfleets.com.br/ws.integracao/positions/v1',
        'client_id' => env('VFLEETS_CLIENT_ID'),
        'client_secret' => env('VFLEETS_CLIENT_SECRET'),
        'grant_type' => 'client_credentials',
        'sync_key' => env('VFLEETS_SYNC_KEY'),

        /*
         * Intervalo mínimo entre duas chamadas à API, em segundos.
         *
         * A tela do Mapa Geral sincroniza ao abrir, e sem esse limite cada
         * usuário que abre a tela gera uma chamada. A API responde 429 e a
         * sincronização inteira falha — nenhum veículo é atualizado, e em três
         * horas todos aparecem como "sem sinal". Dentro do intervalo, a tela
         * usa o que já está no banco.
         */
        'intervalo_sincronizacao' => (int) env('VFLEETS_INTERVALO_SYNC', 120),
    ],

];
