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
    | your .env — never hard-coded.
    |
    | Engines:
    |   yalidine_engine  : yalidine, yalitec, easy_and_speed, economiqua,
    |                      guepex, we_can                       → token + key
    |   procolis_engine  : procolis, zrexpress, abex, colilog,
    |                      flash_delivery, leopard               → id + token
    |   maystro          : maystro                               → token
    |   ecotrack_engine  : ecotrack + 71 sub-providers           → token each
    |   independent      : elogistia, near_delivery, noest,
    |                      ecom_delivery                         → token
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
        'easy_and_speed' => [
            'token' => env('EASY_AND_SPEED_API_TOKEN'),
            'key' => env('EASY_AND_SPEED_API_KEY'),
        ],
        'economiqua' => [
            'token' => env('ECONOMIQUA_API_TOKEN'),
            'key' => env('ECONOMIQUA_API_KEY'),
        ],
        'guepex' => [
            'token' => env('GUEPEX_API_TOKEN'),
            'key' => env('GUEPEX_API_KEY'),
        ],
        'we_can' => [
            'token' => env('WE_CAN_API_TOKEN'),
            'key' => env('WE_CAN_API_KEY'),
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
        'abex' => [
            'id' => env('ABEX_ID'),
            'token' => env('ABEX_TOKEN'),
        ],
        'colilog' => [
            'id' => env('COLILOG_ID'),
            'token' => env('COLILOG_TOKEN'),
        ],
        'flash_delivery' => [
            'id' => env('FLASH_DELIVERY_ID'),
            'token' => env('FLASH_DELIVERY_TOKEN'),
        ],
        'leopard' => [
            'id' => env('LEOPARD_ID'),
            'token' => env('LEOPARD_TOKEN'),
        ],

        // -----------------------------------------------------------------------
        // ZR Express NEW platform (api.zrexpress.app — NOT the legacy Procolis one)
        // -----------------------------------------------------------------------
        'zrexpress_new' => [
            'tenant_id' => env('ZREXPRESS_NEW_TENANT_ID'),
            'api_key' => env('ZREXPRESS_NEW_API_KEY'),
        ],

        // -----------------------------------------------------------------------
        // Zimou Express (delivery router — dispatches to partner carriers)
        // -----------------------------------------------------------------------
        'zimou' => [
            'token' => env('ZIMOU_API_TOKEN'),
        ],

        // -----------------------------------------------------------------------
        // Independent native adapters
        // -----------------------------------------------------------------------
        'elogistia' => [
            'token' => env('ELOGISTIA_API_TOKEN'),
        ],
        'near_delivery' => [
            'token' => env('NEAR_DELIVERY_API_TOKEN'),
        ],
        'noest' => [
            'token' => env('NOEST_API_TOKEN'),
        ],
        'ecom_delivery' => [
            'token' => env('ECOM_DELIVERY_API_TOKEN'),
        ],

        // -----------------------------------------------------------------------
        // Ecotrack engine — generic base
        // -----------------------------------------------------------------------
        'ecotrack' => [
            'token' => env('ECOTRACK_API_TOKEN'),
        ],

        // -----------------------------------------------------------------------
        // Ecotrack-engine sub-providers — original 22
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

        // -----------------------------------------------------------------------
        // Ecotrack-engine sub-providers
        // -----------------------------------------------------------------------
        'alania_express' => [
            'token' => env('ALANIA_EXPRESS_API_TOKEN'),
        ],
        'allo_livraison' => [
            'token' => env('ALLO_LIVRAISON_API_TOKEN'),
        ],
        'amana_speed' => [
            'token' => env('AMANA_SPEED_API_TOKEN'),
        ],
        'aranex' => [
            'token' => env('ARANEX_API_TOKEN'),
        ],
        'areex_delivery' => [
            'token' => env('AREEX_DELIVERY_API_TOKEN'),
        ],
        'atlas_express' => [
            'token' => env('ATLAS_EXPRESS_API_TOKEN'),
        ],
        'bfk_express' => [
            'token' => env('BFK_EXPRESS_API_TOKEN'),
        ],
        'boogi' => [
            'token' => env('BOOGI_API_TOKEN'),
        ],
        'champion_logistics' => [
            'token' => env('CHAMPION_LOGISTICS_API_TOKEN'),
        ],
        'chronorex' => [
            'token' => env('CHRONOREX_API_TOKEN'),
        ],
        'cirta_express' => [
            'token' => env('CIRTA_EXPRESS_API_TOKEN'),
        ],
        'colex' => [
            'token' => env('COLEX_API_TOKEN'),
        ],
        'colireli' => [
            'token' => env('COLIRELI_API_TOKEN'),
        ],
        'colizone' => [
            'token' => env('COLIZONE_API_TOKEN'),
        ],
        'delivro_mail' => [
            'token' => env('DELIVRO_MAIL_API_TOKEN'),
        ],
        'eco_rapide' => [
            'token' => env('ECO_RAPIDE_API_TOKEN'),
        ],
        'el_guide' => [
            'token' => env('EL_GUIDE_API_TOKEN'),
        ],
        'expedia_chrono' => [
            'token' => env('EXPEDIA_CHRONO_API_TOKEN'),
        ],
        'fast_horse' => [
            'token' => env('FAST_HORSE_API_TOKEN'),
        ],
        'fz_delivery' => [
            'token' => env('FZ_DELIVERY_API_TOKEN'),
        ],
        'gs_ecommerce' => [
            'token' => env('GS_ECOMMERCE_API_TOKEN'),
        ],
        'hdd_express' => [
            'token' => env('HDD_EXPRESS_API_TOKEN'),
        ],
        'imir_logistics' => [
            'token' => env('IMIR_LOGISTICS_API_TOKEN'),
        ],
        'jaguar_livraison' => [
            'token' => env('JAGUAR_LIVRAISON_API_TOKEN'),
        ],
        'jo_express' => [
            'token' => env('JO_EXPRESS_API_TOKEN'),
        ],
        'lihlih_express' => [
            'token' => env('LIHLIH_EXPRESS_API_TOKEN'),
        ],
        'lynx' => [
            'token' => env('LYNX_API_TOKEN'),
        ],
        'major_express' => [
            'token' => env('MAJOR_EXPRESS_API_TOKEN'),
        ],
        'mars_express' => [
            'token' => env('MARS_EXPRESS_API_TOKEN'),
        ],
        'mazaya_logistics' => [
            'token' => env('MAZAYA_LOGISTICS_API_TOKEN'),
        ],
        'med_express' => [
            'token' => env('MED_EXPRESS_API_TOKEN'),
        ],
        'navex_delivery' => [
            'token' => env('NAVEX_DELIVERY_API_TOKEN'),
        ],
        'om_courrier' => [
            'token' => env('OM_COURRIER_API_TOKEN'),
        ],
        'on_time_express' => [
            'token' => env('ON_TIME_EXPRESS_API_TOKEN'),
        ],
        'ovred' => [
            'token' => env('OVRED_API_TOKEN'),
        ],
        'pdex' => [
            'token' => env('PDEX_API_TOKEN'),
        ],
        'quick_delivery' => [
            'token' => env('QUICK_DELIVERY_API_TOKEN'),
        ],
        'red_ex' => [
            'token' => env('RED_EX_API_TOKEN'),
        ],
        'rihal_express' => [
            'token' => env('RIHAL_EXPRESS_API_TOKEN'),
        ],
        'rj_360' => [
            'token' => env('RJ_360_API_TOKEN'),
        ],
        'rm_express' => [
            'token' => env('RM_EXPRESS_API_TOKEN'),
        ],
        'rs_express' => [
            'token' => env('RS_EXPRESS_API_TOKEN'),
        ],
        'ruta_express' => [
            'token' => env('RUTA_EXPRESS_API_TOKEN'),
        ],
        'samex' => [
            'token' => env('SAMEX_API_TOKEN'),
        ],
        'sbl_express' => [
            'token' => env('SBL_EXPRESS_API_TOKEN'),
        ],
        'speed_mail' => [
            'token' => env('SPEED_MAIL_API_TOKEN'),
        ],
        'sultan_colis' => [
            'token' => env('SULTAN_COLIS_API_TOKEN'),
        ],
        'swift_express' => [
            'token' => env('SWIFT_EXPRESS_API_TOKEN'),
        ],
        'tawsil_star' => [
            'token' => env('TAWSIL_STAR_API_TOKEN'),
        ],
        'univer_delivery' => [
            'token' => env('UNIVER_DELIVERY_API_TOKEN'),
        ],
        'vitrans' => [
            'token' => env('VITRANS_API_TOKEN'),
        ],
        'weewee_delivery' => [
            'token' => env('WEEWEE_DELIVERY_API_TOKEN'),
        ],
        'win_delivery' => [
            'token' => env('WIN_DELIVERY_API_TOKEN'),
        ],
        'zinya_tec' => [
            'token' => env('ZINYA_TEC_API_TOKEN'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    */
    'http' => [
        'timeout' => (int) env('COURIER_HTTP_TIMEOUT', 45),
        'connect_timeout' => (int) env('COURIER_HTTP_CONNECT_TIMEOUT', 30),
        'retry_times' => (int) env('COURIER_HTTP_RETRY', 3),
        'retry_sleep_ms' => (int) env('COURIER_HTTP_RETRY_SLEEP', 500),
    ],

];
