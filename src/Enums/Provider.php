<?php

declare(strict_types=1);

namespace Uften\Courier\Enums;

use Uften\Courier\Data\ProviderMetadata;

/**
 * Every supported Algerian courier provider.
 *
 * Providers sharing the same API engine are grouped with comments.
 * The enum is the single source of truth for: base URL, adapter class,
 * credential requirements, and display metadata.
 */
enum Provider: string
{
    // -------------------------------------------------------------------------
    // Yalidine engine (2 providers)
    // -------------------------------------------------------------------------
    case YALIDINE = 'yalidine';
    /** Yalitec uses the identical Yalidine API engine with a different subdomain. */
    case YALITEC  = 'yalitec';

    // -------------------------------------------------------------------------
    // Maystro (standalone engine)
    // -------------------------------------------------------------------------
    case MAYSTRO = 'maystro';

    // -------------------------------------------------------------------------
    // Procolis engine (legacy — 2 providers)
    // -------------------------------------------------------------------------
    case PROCOLIS  = 'procolis';
    case ZREXPRESS = 'zrexpress';

    // -------------------------------------------------------------------------
    // ZR Express NEW platform (standalone — api.zrexpress.app)
    // -------------------------------------------------------------------------
    case ZREXPRESS_NEW = 'zrexpress_new';

    // -------------------------------------------------------------------------
    // Zimou Express (standalone router engine)
    // -------------------------------------------------------------------------
    case ZIMOU = 'zimou';

    // -------------------------------------------------------------------------
    // Ecotrack engine — generic base + 22 branded sub-providers
    // -------------------------------------------------------------------------
    case ECOTRACK        = 'ecotrack';
    case ANDERSON        = 'anderson';
    case AREEX           = 'areex';
    case BA_CONSULT      = 'ba_consult';
    case CONEXLOG        = 'conexlog';
    case COYOTE_EXPRESS  = 'coyote_express';
    case DHD             = 'dhd';
    case DISTAZERO       = 'distazero';
    case E48HR           = 'e48hr';
    case FRETDIRECT      = 'fretdirect';
    case GOLIVRI         = 'golivri';
    case MONO_HUB        = 'mono_hub';
    case MSM_GO          = 'msm_go';
    case NEGMAR_EXPRESS  = 'negmar_express';
    case PACKERS         = 'packers';
    case PREST           = 'prest';
    case RB_LIVRAISON    = 'rb_livraison';
    case REX_LIVRAISON   = 'rex_livraison';
    case ROCKET_DELIVERY = 'rocket_delivery';
    case SALVA_DELIVERY  = 'salva_delivery';
    case SPEED_DELIVERY  = 'speed_delivery';
    case TSL_EXPRESS     = 'tsl_express';
    case WORLDEXPRESS    = 'worldexpress';

    // =========================================================================
    // Identity helpers
    // =========================================================================

    public function label(): string
    {
        return $this->metadata()->title;
    }

    public function adapterClass(): string
    {
        return match ($this) {
            self::YALIDINE, self::YALITEC   => \Uften\Courier\Adapters\YalidineAdapter::class,
            self::MAYSTRO                   => \Uften\Courier\Adapters\MaystroAdapter::class,
            self::PROCOLIS, self::ZREXPRESS => \Uften\Courier\Adapters\ProcolisAdapter::class,
            default                         => \Uften\Courier\Adapters\EcotrackAdapter::class,
        };
    }

    public function isYalidineEngine(): bool
    {
        return match ($this) {
            self::YALIDINE, self::YALITEC => true,
            default => false,
        };
    }

    public function isEcotrackEngine(): bool
    {
        return !$this->isYalidineEngine()
            && $this !== self::MAYSTRO
            && $this !== self::PROCOLIS
            && $this !== self::ZREXPRESS
            && $this !== self::ZIMOU
            && $this !== self::ZREXPRESS_NEW;
    }

    public function requiresApiId(): bool
    {
        return match ($this) {
            self::PROCOLIS, self::ZREXPRESS => true,
            default => false,
        };
    }

    // =========================================================================
    // Base API URLs (from original source files)
    // =========================================================================

    public function baseUrl(): string
    {
        return match ($this) {
            self::YALIDINE       => 'https://api.yalidine.app',
            self::YALITEC        => 'https://api.yalitec.me',
            self::MAYSTRO        => 'https://backend.maystro-delivery.com/api',
            self::PROCOLIS,
            self::ZREXPRESS      => 'https://procolis.com/api_v1',
            self::ZIMOU          => 'https://zimou.express/api',
            self::ZREXPRESS_NEW  => 'https://api.zrexpress.app',
            self::ECOTRACK       => 'https://ecotrack.dz',
            self::ANDERSON       => 'https://anderson.ecotrack.dz',
            self::AREEX          => 'https://areex.ecotrack.dz',
            self::BA_CONSULT     => 'https://bacexpress.ecotrack.dz',
            self::CONEXLOG       => 'https://app.conexlog-dz.com',
            self::COYOTE_EXPRESS => 'https://coyoteexpressdz.ecotrack.dz',
            self::DHD            => 'https://dhd.ecotrack.dz',
            self::DISTAZERO      => 'https://distazero.ecotrack.dz',
            self::E48HR          => 'https://48hr.ecotrack.dz',
            self::FRETDIRECT     => 'https://fret.ecotrack.dz',
            self::GOLIVRI        => 'https://golivri.ecotrack.dz',
            self::MONO_HUB       => 'https://mono.ecotrack.dz',
            self::MSM_GO         => 'https://msmgo.ecotrack.dz',
            self::NEGMAR_EXPRESS => 'https://negmar.ecotrack.dz',
            self::PACKERS        => 'https://packers.ecotrack.dz',
            self::PREST          => 'https://prest.ecotrack.dz',
            self::RB_LIVRAISON   => 'https://rblivraison.ecotrack.dz',
            self::REX_LIVRAISON  => 'https://rex.ecotrack.dz',
            self::ROCKET_DELIVERY => 'https://rocket.ecotrack.dz',
            self::SALVA_DELIVERY => 'https://salvadelivery.ecotrack.dz',
            self::SPEED_DELIVERY => 'https://speeddelivery.ecotrack.dz',
            self::TSL_EXPRESS    => 'https://tsl.ecotrack.dz',
            self::WORLDEXPRESS   => 'https://worldexpress.ecotrack.dz',
        };
    }

    // =========================================================================
    // Metadata (faithfully transcribed from original provider source files)
    // =========================================================================

    public function metadata(): ProviderMetadata
    {
        return match ($this) {
            self::YALIDINE => new ProviderMetadata(
                name: 'Yalidine', title: 'Yalidine',
                website: 'https://yalidine.com/',
                description: 'Yalidine société de livraison en Algérie offre un service de livraison rapide et sécurisé.',
                logo: 'https://yalidine.com/assets/img/yalidine-logo.png',
                apiDocs: 'https://yalidine.app/app/dev/docs/api/index.php',
                support: 'https://yalidine.com/#contact',
                trackingUrl: 'https://yalidine.com/suivre-un-colis/',
            ),
            self::YALITEC => new ProviderMetadata(
                name: 'Yalitec', title: 'Yalitec',
                website: 'https://www.yalitec.com/fr',
                description: 'Yalitec société de livraison en Algérie offre un service de livraison rapide et sécurisé.',
                logo: 'https://www.yalitec.com/_next/image?url=%2Fimages%2Flogo.png&w=384&q=75',
                apiDocs: 'https://yalitec.me/app/dev/docs/api/index.php',
                support: 'https://www.yalitec.com/fr#contact',
                trackingUrl: null,
            ),
            self::MAYSTRO => new ProviderMetadata(
                name: 'MaystroDelivery', title: 'Maystro Delivery',
                website: 'https://maystro-delivery.com/',
                description: 'Maystro Delivery société de livraison en Algérie offre un service de livraison rapide et sécurisé.',
                logo: 'https://maystro-delivery.com/img/Maystro-blue-extonly.svg',
                apiDocs: 'https://maystro.gitbook.io/maystro-delivery-documentation',
                support: 'https://maystro-delivery.com/ContactUS.html',
                trackingUrl: 'https://maystro-delivery.com/trackingSD.html',
            ),
            self::PROCOLIS => new ProviderMetadata(
                name: 'Procolis', title: 'Procolis',
                website: 'https://procolis.com',
                description: 'Procolis est une plateforme de gestion de livraison en Algérie.',
                logo: null, apiDocs: 'https://procolis.com',
                support: 'https://procolis.com', trackingUrl: null,
            ),
            self::ZREXPRESS => new ProviderMetadata(
                name: 'ZRExpress', title: 'ZR Express',
                website: 'https://zrexpress.com',
                description: 'ZRexpress société de livraison en Algérie offre un service de livraison rapide et sécurisé.',
                logo: 'https://zrexpress.com/ZREXPRESS_WEB/ext/Logo.jpg',
                apiDocs: 'https://zrexpress.com/ZREXPRESS_WEB/FR/Developpement.awp',
                support: 'https://www.facebook.com/ZRexpresslivraison/',
                trackingUrl: null,
            ),
            self::ECOTRACK => new ProviderMetadata(
                name: 'Ecotrack', title: 'Ecotrack',
                website: 'https://ecotrack.dz',
                description: 'Ecotrack est une plateforme multi-transporteurs pour la livraison en Algérie (DHD, Conexlog/UPS et plus).',
                logo: null, apiDocs: 'https://ecotrack.dz',
                support: 'https://ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::ANDERSON => new ProviderMetadata(
                name: 'AndersonDelivery', title: 'Anderson Delivery',
                website: 'https://anderson.ecotrack.dz/',
                description: 'Anderson Delivery est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: 'https://cdn1.ecotrack.dz/anderson/images/login_logoctVbSeP.png',
                apiDocs: 'https://anderson.ecotrack.dz/', support: 'https://anderson.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::AREEX => new ProviderMetadata(
                name: 'Areex', title: 'Areex',
                website: 'https://areex.ecotrack.dz/',
                description: 'Areex est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: null, apiDocs: 'https://areex.ecotrack.dz/', support: 'https://areex.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::BA_CONSULT => new ProviderMetadata(
                name: 'BaConsult', title: 'BA Consult',
                website: 'https://bacexpress.ecotrack.dz/',
                description: 'BA Consult est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: 'https://cdn1.ecotrack.dz/bacexpress/images/login_logoeORMVno.png',
                apiDocs: 'https://bacexpress.ecotrack.dz/', support: 'https://bacexpress.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::CONEXLOG => new ProviderMetadata(
                name: 'Conexlog', title: 'Conexlog',
                website: 'https://conexlog-dz.com/',
                description: 'CONEXLOG est le prestataire exclusif des services agréés en Algérie pour le groupe UPS.',
                logo: 'https://conexlog-dz.com/assets/img/logo.png',
                apiDocs: 'https://conexlog-dz.com/', support: 'https://conexlog-dz.com/contact.php',
                trackingUrl: 'https://conexlog-dz.com/suivi.php',
            ),
            self::COYOTE_EXPRESS => new ProviderMetadata(
                name: 'CoyoteExpress', title: 'Coyote Express',
                website: 'https://coyoteexpressdz.ecotrack.dz/',
                description: 'Coyote Express est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: null, apiDocs: 'https://coyoteexpressdz.ecotrack.dz/',
                support: 'https://coyoteexpressdz.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::DHD => new ProviderMetadata(
                name: 'Dhd', title: 'DHD',
                website: 'https://dhd-dz.com/',
                description: 'DHD livraison est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: 'https://dhd-dz.com/assets/img/logo.png',
                apiDocs: 'https://dhd-dz.com/', support: 'https://dhd-dz.com/#contact',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::DISTAZERO => new ProviderMetadata(
                name: 'Distazero', title: 'Distazero',
                website: 'https://distazero.ecotrack.dz/',
                description: 'Distazero est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: 'https://cdn1.ecotrack.dz/distazero/images/login_logooI8OebS.png',
                apiDocs: 'https://distazero.ecotrack.dz/', support: 'https://distazero.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::E48HR => new ProviderMetadata(
                name: 'E48hrLivraison', title: '48Hr Livraison',
                website: 'https://48hr.ecotrack.dz/',
                description: '48Hr Livraison est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: null, apiDocs: 'https://48hr.ecotrack.dz/', support: 'https://48hr.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::FRETDIRECT => new ProviderMetadata(
                name: 'Fretdirect', title: 'FRET.Direct',
                website: 'https://fret.ecotrack.dz/',
                description: 'FRET.Direct est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: null, apiDocs: 'https://fret.ecotrack.dz/', support: 'https://fret.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::GOLIVRI => new ProviderMetadata(
                name: 'Golivri', title: 'GOLIVRI',
                website: 'https://golivri.ecotrack.dz/',
                description: 'GOLIVRI est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: 'https://cdn1.ecotrack.dz/golivri/images/login_logoP2208XU.png',
                apiDocs: 'https://golivri.ecotrack.dz/', support: 'https://golivri.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::MONO_HUB => new ProviderMetadata(
                name: 'MonoHub', title: 'Mono Hub',
                website: 'https://mono.ecotrack.dz/',
                description: 'Mono Hub est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: null, apiDocs: 'https://mono.ecotrack.dz/', support: 'https://mono.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::MSM_GO => new ProviderMetadata(
                name: 'MsmGo', title: 'MSM Go',
                website: 'https://msmgo.ecotrack.dz',
                description: 'MSM Go est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: null, apiDocs: 'https://msmgo.ecotrack.dz', support: 'https://msmgo.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::NEGMAR_EXPRESS => new ProviderMetadata(
                name: 'NegmarExpress', title: 'Negmar Express',
                website: 'https://negmar.ecotrack.dz/',
                description: 'Negmar Express est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: null, apiDocs: 'https://negmar.ecotrack.dz/', support: 'https://negmar.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::PACKERS => new ProviderMetadata(
                name: 'Packers', title: 'Packers',
                website: 'https://packers.ecotrack.dz/',
                description: 'Packers est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: null, apiDocs: 'https://packers.ecotrack.dz/', support: 'https://packers.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::PREST => new ProviderMetadata(
                name: 'Prest', title: 'Prest',
                website: 'https://prest.ecotrack.dz/',
                description: 'Prest est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: null, apiDocs: 'https://prest.ecotrack.dz/', support: 'https://prest.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::RB_LIVRAISON => new ProviderMetadata(
                name: 'RbLivraison', title: 'RB Livraison',
                website: 'https://rblivraison.ecotrack.dz/',
                description: 'RB Livraison est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: null, apiDocs: 'https://rblivraison.ecotrack.dz/', support: 'https://rblivraison.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::REX_LIVRAISON => new ProviderMetadata(
                name: 'RexLivraison', title: 'Rex Livraison',
                website: 'https://rex.ecotrack.dz/',
                description: 'Rex Livraison est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: 'https://cdn1.ecotrack.dz/rex/images/login_logoCu3Rwdm.png',
                apiDocs: 'https://rex.ecotrack.dz/', support: 'https://rex.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::ROCKET_DELIVERY => new ProviderMetadata(
                name: 'RocketDelivery', title: 'Rocket Delivery',
                website: 'https://rocket.ecotrack.dz/',
                description: 'Rocket Delivery est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: 'https://cdn1.ecotrack.dz/rocket/images/login_logogAux6nt.png',
                apiDocs: 'https://rocket.ecotrack.dz/', support: 'https://rocket.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::SALVA_DELIVERY => new ProviderMetadata(
                name: 'SalvaDelivery', title: 'Salva Delivery',
                website: 'https://salvadelivery.ecotrack.dz/',
                description: 'Salva Delivery est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: 'https://cdn1.ecotrack.dz/salvadelivery/images/login_logo6GOyzNz.png',
                apiDocs: 'https://salvadelivery.ecotrack.dz/', support: 'https://salvadelivery.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::SPEED_DELIVERY => new ProviderMetadata(
                name: 'SpeedDelivery', title: 'Speed Delivery',
                website: 'https://speeddelivery.ecotrack.dz/',
                description: 'Speed Delivery est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: null, apiDocs: 'https://speeddelivery.ecotrack.dz/', support: 'https://speeddelivery.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::TSL_EXPRESS => new ProviderMetadata(
                name: 'TslExpress', title: 'TSL Express',
                website: 'https://tsl.ecotrack.dz/',
                description: 'TSL Express est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: 'https://cdn1.ecotrack.dz/tsl/images/login_logoxDIzsCJ.png',
                apiDocs: 'https://tsl.ecotrack.dz/', support: 'https://tsl.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::ZIMOU => new ProviderMetadata(
                name: 'ZimouExpress', title: 'Zimou Express',
                website: 'https://zimou.express',
                description: 'Zimou Express est un routeur de livraison en Algérie qui dispatche automatiquement les colis vers le meilleur transporteur partenaire.',
                logo: null,
                apiDocs: 'https://zimou.express/api/docs',
                support: 'https://zimou.express',
                trackingUrl: 'https://zimou.express',
            ),
            self::ZREXPRESS_NEW => new ProviderMetadata(
                name: 'ZRExpressNew', title: 'ZR Express NEW',
                website: 'https://zrexpress.app',
                description: 'La nouvelle plateforme ZR Express — API REST moderne remplaçant l\'ancienne intégration Procolis.',
                logo: null,
                apiDocs: 'https://docs.zrexpress.app/reference/createparcelendpoint',
                support: 'mailto:support@zrexpress.net',
                trackingUrl: null,
            ),
            self::WORLDEXPRESS => new ProviderMetadata(
                name: 'Worldexpress', title: 'WorldExpress',
                website: 'https://worldexpress.ecotrack.dz/',
                description: 'WorldExpress est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: null, apiDocs: 'https://worldexpress.ecotrack.dz/', support: 'https://worldexpress.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
        };
    }
}
