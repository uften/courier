<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Provider
    |--------------------------------------------------------------------------
    */
    'default' => env('COURIER_PROVIDER', 'yalidine'),

    /*
    |--------------------------------------------------------------------------
    | Provider Credentials
    |--------------------------------------------------------------------------
    |
    | Add credentials for each provider you use. All values should live in
    | your .env — never hard-coded. Ecotrack sub-providers each need their
    | own token even though they share the same API engine.
    |
    | Engines:
    |   yalidine_engine : yalidine, yalitec           → token + key
    |   procolis_engine : procolis, zrexpress          → id + token
    |   maystro         : maystro                      → token
    |   ecotrack_engine : ecotrack + 22 sub-providers  → token each
    |
    */
    'providers' => [

        // -----------------------------------------------------------------------
        // Yalidine engine
        // -----------------------------------------------------------------------
        'yalidine' => [
            'token' => env('YALIDINE_API_TOKEN'),   // X-API-ID
            'key' => env('YALIDINE_API_KEY'),     // X-API-TOKEN
        ],
        'yalitec' => [
            'token' => env('YALITEC_API_TOKEN'),
            'key' => env('YALITEC_API_KEY'),
        ],

        // -----------------------------------------------------------------------
        // Maystro
        // -----------------------------------------------------------------------
        'maystro' => [
            'token' => env('MAYSTRO_API_TOKEN'),
        ],

        // -----------------------------------------------------------------------
        // Procolis engine
        // -----------------------------------------------------------------------
        'procolis' => [
            'id' => env('PROCOLIS_ID'),
            'token' => env('PROCOLIS_TOKEN'),
        ],
        'zrexpress' => [
            'id' => env('ZREXPRESS_ID'),
            'token' => env('ZREXPRESS_TOKEN'),
        ],

        // -----------------------------------------------------------------------
        // Zimou Express (delivery router — dispatches to partner carriers)
        // -----------------------------------------------------------------------
        'zimou' => [
            'token' => env('ZIMOU_API_TOKEN'),
        ],

        // -----------------------------------------------------------------------
        // Ecotrack engine — generic base
        // -----------------------------------------------------------------------
        'ecotrack' => [
            'token' => env('ECOTRACK_API_TOKEN'),
        ],

        // -----------------------------------------------------------------------
        // Ecotrack-engine sub-providers (each has its own subdomain + token)
        // -----------------------------------------------------------------------
        'anderson' => [
            'token' => env('ANDERSON_API_TOKEN'),
        ],
        'areex' => [
            'token' => env('AREEX_API_TOKEN'),
        ],
        'ba_consult' => [
            'token' => env('BA_CONSULT_API_TOKEN'),
        ],
        'conexlog' => [
            'token' => env('CONEXLOG_API_TOKEN'),
        ],
        'coyote_express' => [
            'token' => env('COYOTE_EXPRESS_API_TOKEN'),
        ],
        'dhd' => [
            'token' => env('DHD_API_TOKEN'),
        ],
        'distazero' => [
            'token' => env('DISTAZERO_API_TOKEN'),
        ],
        'e48hr' => [
            'token' => env('E48HR_API_TOKEN'),
        ],
        'fretdirect' => [
            'token' => env('FRETDIRECT_API_TOKEN'),
        ],
        'golivri' => [
            'token' => env('GOLIVRI_API_TOKEN'),
        ],
        'mono_hub' => [
            'token' => env('MONO_HUB_API_TOKEN'),
        ],
        'msm_go' => [
            'token' => env('MSM_GO_API_TOKEN'),
        ],
        'negmar_express' => [
            'token' => env('NEGMAR_EXPRESS_API_TOKEN'),
        ],
        'packers' => [
            'token' => env('PACKERS_API_TOKEN'),
        ],
        'prest' => [
            'token' => env('PREST_API_TOKEN'),
        ],
        'rb_livraison' => [
            'token' => env('RB_LIVRAISON_API_TOKEN'),
        ],
        'rex_livraison' => [
            'token' => env('REX_LIVRAISON_API_TOKEN'),
        ],
        'rocket_delivery' => [
            'token' => env('ROCKET_DELIVERY_API_TOKEN'),
        ],
        'salva_delivery' => [
            'token' => env('SALVA_DELIVERY_API_TOKEN'),
        ],
        'speed_delivery' => [
            'token' => env('SPEED_DELIVERY_API_TOKEN'),
        ],
        'tsl_express' => [
            'token' => env('TSL_EXPRESS_API_TOKEN'),
        ],
        'worldexpress' => [
            'token' => env('WORLDEXPRESS_API_TOKEN'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    */
    'http' => [
        'timeout' => (int) env('COURIER_HTTP_TIMEOUT', 30),
        'connect_timeout' => (int) env('COURIER_HTTP_CONNECT_TIMEOUT', 10),
        'retry_times' => (int) env('COURIER_HTTP_RETRY', 1),
        'retry_sleep_ms' => (int) env('COURIER_HTTP_RETRY_SLEEP', 200),
    ],

];
