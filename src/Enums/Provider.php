<?php

declare(strict_types=1);

namespace Uften\Courier\Enums;

use Uften\Courier\Adapters\EcomDeliveryAdapter;
use Uften\Courier\Adapters\EcotrackAdapter;
use Uften\Courier\Adapters\ElogistiaAdapter;
use Uften\Courier\Adapters\MaystroAdapter;
use Uften\Courier\Adapters\NearDeliveryAdapter;
use Uften\Courier\Adapters\NoestAdapter;
use Uften\Courier\Adapters\ProcolisAdapter;
use Uften\Courier\Adapters\YalidineAdapter;
use Uften\Courier\Data\ProviderMetadata;

/**
 * Every supported Algerian courier provider.
 *
 * Providers sharing the same API engine are grouped with comments.
 * The enum is the single source of truth for: base URL, adapter class,
 * credential requirements, logo assets, and display metadata.
 *
 * Total: 91 providers.
 *
 * Engine groups:
 *   - Yalidine engine  : 6 providers
 *   - Maystro          : 1 provider
 *   - Procolis engine  : 6 providers (incl. ABEX, Colilog, Flash, Leopard)
 *   - ZR Express NEW   : 1 provider (standalone)
 *   - Zimou Express    : 1 provider (standalone router)
 *   - Independent      : 4 providers (Elogistia, Near, Noest, E-COM)
 *   - Ecotrack engine  : 72 providers (base + sub-providers)
 */
enum Provider: string
{
    // -------------------------------------------------------------------------
    // Yalidine engine (6 providers)
    // -------------------------------------------------------------------------
    case YALIDINE = 'yalidine';
    case YALITEC = 'yalitec';
    case EASY_AND_SPEED = 'easy_and_speed';
    case ECONOMIQUA = 'economiqua';
    case GUEPEX = 'guepex';
    case WE_CAN = 'we_can';

    // -------------------------------------------------------------------------
    // Maystro (standalone engine)
    // -------------------------------------------------------------------------
    case MAYSTRO = 'maystro';

    // -------------------------------------------------------------------------
    // Procolis engine (6 providers)
    // ABEX, Colilog, Flash Delivery & Leopard use procolis.com/api_v1.
    // -------------------------------------------------------------------------
    case PROCOLIS = 'procolis';
    case ZREXPRESS = 'zrexpress';
    case ABEX = 'abex';
    case COLILOG = 'colilog';
    case FLASH_DELIVERY = 'flash_delivery';
    case LEOPARD = 'leopard';

    // -------------------------------------------------------------------------
    // ZR Express NEW platform (standalone — api.zrexpress.app)
    // -------------------------------------------------------------------------
    case ZREXPRESS_NEW = 'zrexpress_new';

    // -------------------------------------------------------------------------
    // Zimou Express (standalone router engine)
    // -------------------------------------------------------------------------
    case ZIMOU = 'zimou';

    // -------------------------------------------------------------------------
    // Independent native adapters (own API surface)
    // -------------------------------------------------------------------------
    case ELOGISTIA = 'elogistia';
    case NEAR_DELIVERY = 'near_delivery';
    case NOEST = 'noest';
    case ECOM_DELIVERY = 'ecom_delivery';

    // -------------------------------------------------------------------------
    // Ecotrack engine — generic base + 71 branded sub-providers
    // -------------------------------------------------------------------------
    case ECOTRACK = 'ecotrack';

    // Original 22 Ecotrack sub-providers
    case ANDERSON = 'anderson';
    case AREEX = 'areex';
    case BA_CONSULT = 'ba_consult';
    case CONEXLOG = 'conexlog';
    case COYOTE_EXPRESS = 'coyote_express';
    case DHD = 'dhd';
    case DISTAZERO = 'distazero';
    case E48HR = 'e48hr';
    case FRETDIRECT = 'fretdirect';
    case GOLIVRI = 'golivri';
    case MONO_HUB = 'mono_hub';
    case MSM_GO = 'msm_go';
    case NEGMAR_EXPRESS = 'negmar_express';
    case PACKERS = 'packers';
    case PREST = 'prest';
    case RB_LIVRAISON = 'rb_livraison';
    case REX_LIVRAISON = 'rex_livraison';
    case ROCKET_DELIVERY = 'rocket_delivery';
    case SALVA_DELIVERY = 'salva_delivery';
    case SPEED_DELIVERY = 'speed_delivery';
    case TSL_EXPRESS = 'tsl_express';
    case WORLDEXPRESS = 'worldexpress';

    // New Ecotrack sub-providers
    case ALANIA_EXPRESS = 'alania_express';
    case ALLO_LIVRAISON = 'allo_livraison';
    case AMANA_SPEED = 'amana_speed';
    case ARANEX = 'aranex';
    case AREEX_DELIVERY = 'areex_delivery';
    case ATLAS_EXPRESS = 'atlas_express';
    case BFK_EXPRESS = 'bfk_express';
    case BOOGI = 'boogi';
    case CHAMPION_LOGISTICS = 'champion_logistics';
    case CHRONOREX = 'chronorex';
    case CIRTA_EXPRESS = 'cirta_express';
    case COLEX = 'colex';
    case COLIRELI = 'colireli';
    case COLIZONE = 'colizone';
    case DELIVRO_MAIL = 'delivro_mail';
    case ECO_RAPIDE = 'eco_rapide';
    case EL_GUIDE = 'el_guide';
    case EXPEDIA_CHRONO = 'expedia_chrono';
    case FAST_HORSE = 'fast_horse';
    case FZ_DELIVERY = 'fz_delivery';
    case GS_ECOMMERCE = 'gs_ecommerce';
    case HDD_EXPRESS = 'hdd_express';
    case IMIR_LOGISTICS = 'imir_logistics';
    case JAGUAR_LIVRAISON = 'jaguar_livraison';
    case JO_EXPRESS = 'jo_express';
    case LIHLIH_EXPRESS = 'lihlih_express';
    case LYNX = 'lynx';
    case MAJOR_EXPRESS = 'major_express';
    case MARS_EXPRESS = 'mars_express';
    case MAZAYA_LOGISTICS = 'mazaya_logistics';
    case MED_EXPRESS = 'med_express';
    case NAVEX_DELIVERY = 'navex_delivery';
    case OM_COURRIER = 'om_courrier';
    case ON_TIME_EXPRESS = 'on_time_express';
    case OVRED = 'ovred';
    case PDEX = 'pdex';
    case QUICK_DELIVERY = 'quick_delivery';
    case RED_EX = 'red_ex';
    case RIHAL_EXPRESS = 'rihal_express';
    case RJ_360 = 'rj_360';
    case RM_EXPRESS = 'rm_express';
    case RS_EXPRESS = 'rs_express';
    case RUTA_EXPRESS = 'ruta_express';
    case SAMEX = 'samex';
    case SBL_EXPRESS = 'sbl_express';
    case SPEED_MAIL = 'speed_mail';
    case SULTAN_COLIS = 'sultan_colis';
    case SWIFT_EXPRESS = 'swift_express';
    case TAWSIL_STAR = 'tawsil_star';
    case UNIVER_DELIVERY = 'univer_delivery';
    case VITRANS = 'vitrans';
    case WEEWEE_DELIVERY = 'weewee_delivery';
    case WIN_DELIVERY = 'win_delivery';
    case ZINYA_TEC = 'zinya_tec';

    // =========================================================================
    // Identity helpers
    // =========================================================================

    public function label(): string
    {
        return $this->metadata()->title;
    }

    public function adapterClass(): string
    {
        return match (true) {
            $this->isYalidineEngine() => YalidineAdapter::class,
            $this === self::MAYSTRO => MaystroAdapter::class,
            $this->isProcolisEngine() => ProcolisAdapter::class,
            $this === self::ELOGISTIA => ElogistiaAdapter::class,
            $this === self::NEAR_DELIVERY => NearDeliveryAdapter::class,
            $this === self::NOEST => NoestAdapter::class,
            $this === self::ECOM_DELIVERY => EcomDeliveryAdapter::class,
            default => EcotrackAdapter::class,
        };
    }

    public function isYalidineEngine(): bool
    {
        return match ($this) {
            self::YALIDINE,
            self::YALITEC,
            self::EASY_AND_SPEED,
            self::ECONOMIQUA,
            self::GUEPEX,
            self::WE_CAN => true,
            default => false,
        };
    }

    public function isProcolisEngine(): bool
    {
        return match ($this) {
            self::PROCOLIS,
            self::ZREXPRESS,
            self::ABEX,
            self::COLILOG,
            self::FLASH_DELIVERY,
            self::LEOPARD => true,
            default => false,
        };
    }

    public function isEcotrackEngine(): bool
    {
        return ! $this->isYalidineEngine()
            && ! $this->isProcolisEngine()
            && $this !== self::MAYSTRO
            && $this !== self::ZIMOU
            && $this !== self::ZREXPRESS_NEW
            && $this !== self::ELOGISTIA
            && $this !== self::NEAR_DELIVERY
            && $this !== self::NOEST
            && $this !== self::ECOM_DELIVERY;
    }

    /** Procolis engine requires an API id in addition to the token. */
    public function requiresApiId(): bool
    {
        return $this->isProcolisEngine();
    }

    // =========================================================================
    // Logo helpers
    // =========================================================================

    /**
     * Returns the bare filename of the carrier logo inside resources/assets/logo/.
     * Published to public/vendor/courier/logo/<filename>.
     */
    public function getLogoFileName(): string
    {
        return match ($this) {
            self::YALIDINE => 'yalidine.png',
            self::YALITEC => 'yalitec.png',
            self::EASY_AND_SPEED => 'easy-and-speed.png',
            self::ECONOMIQUA => 'economiqua.png',
            self::GUEPEX => 'guepex.png',
            self::WE_CAN => 'we-can.png',
            self::MAYSTRO => 'maystro-delivery.png',
            self::PROCOLIS => 'zrexpress.png',
            self::ZREXPRESS => 'zrexpress.png',
            self::ZREXPRESS_NEW => 'zrexpress.png',
            self::ABEX => 'abex.png',
            self::COLILOG => 'colilog.png',
            self::FLASH_DELIVERY => 'flash-delivery.png',
            self::LEOPARD => 'leopard-express.png',
            self::ZIMOU => 'zimou-express.png',
            self::ELOGISTIA => 'elogistia.png',
            self::NEAR_DELIVERY => 'near-delivery.png',
            self::NOEST => 'noest.png',
            self::ECOM_DELIVERY => 'ecom-delivery.png',
            self::ECOTRACK => 'ecotrack.png',
            self::ANDERSON => 'anderson.png',
            self::AREEX => 'areex-delivery.png',
            self::BA_CONSULT => 'ba-express.png',
            self::CONEXLOG => 'conexlog.png',
            self::COYOTE_EXPRESS => 'coyote-express.png',
            self::DHD => 'dhd.png',
            self::DISTAZERO => 'distazero.png',
            self::E48HR => '48hr.png',
            self::FRETDIRECT => 'fret-direct.png',
            self::GOLIVRI => 'golivri.png',
            self::MONO_HUB => 'ecotrack.png',
            self::MSM_GO => 'msmgo.png',
            self::NEGMAR_EXPRESS => 'ecotrack.png',
            self::PACKERS => 'packers.png',
            self::PREST => 'prest.png',
            self::RB_LIVRAISON => 'rb-livraison.png',
            self::REX_LIVRAISON => 'rex-livraison.png',
            self::ROCKET_DELIVERY => 'rocket-delivery.png',
            self::SALVA_DELIVERY => 'salva-delivery.png',
            self::SPEED_DELIVERY => 'ecotrack.png',
            self::TSL_EXPRESS => 'tsl-express.png',
            self::WORLDEXPRESS => 'world-express.png',
            self::ALANIA_EXPRESS => 'alania-express.png',
            self::ALLO_LIVRAISON => 'allo-livraison.png',
            self::AMANA_SPEED => 'amana-speed.png',
            self::ARANEX => 'aranex.png',
            self::AREEX_DELIVERY => 'areex-delivery.png',
            self::ATLAS_EXPRESS => 'atlas-express.png',
            self::BFK_EXPRESS => 'bfk-express.png',
            self::BOOGI => 'boogi.png',
            self::CHAMPION_LOGISTICS => 'champion-logistics.png',
            self::CHRONOREX => 'chronorex.png',
            self::CIRTA_EXPRESS => 'cirta-express.png',
            self::COLEX => 'colex.png',
            self::COLIRELI => 'colireli.png',
            self::COLIZONE => 'colizone.png',
            self::DELIVRO_MAIL => 'delivro-mail.png',
            self::ECO_RAPIDE => 'ecotrack.png',
            self::EL_GUIDE => 'elguide-delivery.png',
            self::EXPEDIA_CHRONO => 'expedia-chrono.png',
            self::FAST_HORSE => 'fast-horse-express.png',
            self::FZ_DELIVERY => 'fz-delivery.png',
            self::GS_ECOMMERCE => 'gs-ecommerce.png',
            self::HDD_EXPRESS => 'hdd-express.png',
            self::IMIR_LOGISTICS => 'imir-logistics.png',
            self::JAGUAR_LIVRAISON => 'jaguar-livraison.png',
            self::JO_EXPRESS => 'jo-express.png',
            self::LIHLIH_EXPRESS => 'lihlih-express.png',
            self::LYNX => 'lynx-express.png',
            self::MAJOR_EXPRESS => 'major-ex.png',
            self::MARS_EXPRESS => 'mars-express.png',
            self::MAZAYA_LOGISTICS => 'mazaya-logistics.png',
            self::MED_EXPRESS => 'med-express.png',
            self::NAVEX_DELIVERY => 'navex-delivery.png',
            self::OM_COURRIER => 'om-express.png',
            self::ON_TIME_EXPRESS => 'on-time-express.png',
            self::OVRED => 'ovred.png',
            self::PDEX => 'pdex.png',
            self::QUICK_DELIVERY => 'quick-delivery.png',
            self::RED_EX => 'red-ex.png',
            self::RIHAL_EXPRESS => 'rihal-express.png',
            self::RJ_360 => 'rj360.png',
            self::RM_EXPRESS => 'rm-express.png',
            self::RS_EXPRESS => 'rs-express.png',
            self::RUTA_EXPRESS => 'ruta-express.png',
            self::SAMEX => 'samex.png',
            self::SBL_EXPRESS => 'sbl-express.png',
            self::SPEED_MAIL => 'speed-mail.png',
            self::SULTAN_COLIS => 'sultan-colis-express.png',
            self::SWIFT_EXPRESS => 'swift-express.png',
            self::TAWSIL_STAR => 'tawsil-star.png',
            self::UNIVER_DELIVERY => 'univer-delivery.png',
            self::VITRANS => 'vitrans.png',
            self::WEEWEE_DELIVERY => 'weewee-delivery.png',
            self::WIN_DELIVERY => 'win-delivery.png',
            self::ZINYA_TEC => 'zinya-tec.png',
        };
    }

    /**
     * Returns the public URL for the carrier logo.
     * Resolves via Laravel's asset() helper after publishing with the
     * `courier-assets` tag:
     *   php artisan vendor:publish --tag=courier-assets
     */
    public function getLogoUrl(): string
    {
        return asset('vendor/courier/logo/'.$this->getLogoFileName());
    }

    // =========================================================================
    // Base API URLs
    // =========================================================================

    public function baseUrl(): string
    {
        return match ($this) {
            // Yalidine engine
            self::YALIDINE => 'https://api.yalidine.app',
            self::YALITEC => 'https://api.yalitec.me',
            self::EASY_AND_SPEED => 'https://api.easyandspeed.app',
            self::ECONOMIQUA => 'https://api.economiqua.app',
            self::GUEPEX => 'https://api.guepex.app',
            self::WE_CAN => 'https://api.wecanservices.me/v1',

            // Maystro
            self::MAYSTRO => 'https://backend.maystro-delivery.com/api',

            // Procolis engine
            self::PROCOLIS,
            self::ZREXPRESS,
            self::ABEX,
            self::COLILOG,
            self::FLASH_DELIVERY,
            self::LEOPARD => 'https://procolis.com/api_v1',

            // Standalone
            self::ZREXPRESS_NEW => 'https://api.zrexpress.app',
            self::ZIMOU => 'https://zimou.express/api',

            // Independent adapters
            self::ELOGISTIA => 'https://api.elogistia.com',
            self::NEAR_DELIVERY => 'https://api.neardelivery.app/api/v1',
            self::NOEST => 'https://app.noest-dz.com',
            self::ECOM_DELIVERY => 'https://ecom-dz.net',

            // Ecotrack engine — existing
            self::ECOTRACK => 'https://ecotrack.dz',
            self::ANDERSON => 'https://anderson.ecotrack.dz',
            self::AREEX => 'https://areex.ecotrack.dz',
            self::BA_CONSULT => 'https://bacexpress.ecotrack.dz',
            self::CONEXLOG => 'https://app.conexlog-dz.com',
            self::COYOTE_EXPRESS => 'https://coyoteexpressdz.ecotrack.dz',
            self::DHD => 'https://dhd.ecotrack.dz',
            self::DISTAZERO => 'https://distazero.ecotrack.dz',
            self::E48HR => 'https://48hr.ecotrack.dz',
            self::FRETDIRECT => 'https://fret.ecotrack.dz',
            self::GOLIVRI => 'https://golivri.ecotrack.dz',
            self::MONO_HUB => 'https://mono.ecotrack.dz',
            self::MSM_GO => 'https://msmgo.ecotrack.dz',
            self::NEGMAR_EXPRESS => 'https://negmar.ecotrack.dz',
            self::PACKERS => 'https://packers.ecotrack.dz',
            self::PREST => 'https://prest.ecotrack.dz',
            self::RB_LIVRAISON => 'https://rblivraison.ecotrack.dz',
            self::REX_LIVRAISON => 'https://rex.ecotrack.dz',
            self::ROCKET_DELIVERY => 'https://rocket.ecotrack.dz',
            self::SALVA_DELIVERY => 'https://salvadelivery.ecotrack.dz',
            self::SPEED_DELIVERY => 'https://speeddelivery.ecotrack.dz',
            self::TSL_EXPRESS => 'https://tsl.ecotrack.dz',
            self::WORLDEXPRESS => 'https://worldexpress.ecotrack.dz',

            // Ecotrack engine
            self::ALANIA_EXPRESS => 'https://alania.ecotrack.dz',
            self::ALLO_LIVRAISON => 'https://allolivraison.ecotrack.dz',
            self::AMANA_SPEED => 'https://amana.ecotrack.dz',
            self::ARANEX => 'https://aranex.ecotrack.dz',
            self::AREEX_DELIVERY => 'https://areex.ecotrack.dz',
            self::ATLAS_EXPRESS => 'https://atlaexpress.ecotrack.dz',
            self::BFK_EXPRESS => 'https://bfkexpress.ecotrack.dz',
            self::BOOGI => 'https://boogi.ecotrack.dz',
            self::CHAMPION_LOGISTICS => 'https://champion.ecotrack.dz',
            self::CHRONOREX => 'https://chronorex.ecotrack.dz',
            self::CIRTA_EXPRESS => 'https://cirtaexpress.ecotrack.dz',
            self::COLEX => 'https://colex.ecotrack.dz',
            self::COLIRELI => 'https://colireli.ecotrack.dz',
            self::COLIZONE => 'https://colizone.ecotrack.dz',
            self::DELIVRO_MAIL => 'https://delivromail.ecotrack.dz',
            self::ECO_RAPIDE => 'https://ecorapide-express.ecotrack.dz',
            self::EL_GUIDE => 'https://elguidedelivery.ecotrack.dz',
            self::EXPEDIA_CHRONO => 'https://expediachrono.ecotrack.dz',
            self::FAST_HORSE => 'https://fasthorse.ecotrack.dz',
            self::FZ_DELIVERY => 'https://fzdelivery.ecotrack.dz',
            self::GS_ECOMMERCE => 'https://gsecommerce.ecotrack.dz',
            self::HDD_EXPRESS => 'https://hhdexpress.ecotrack.dz',
            self::IMIR_LOGISTICS => 'https://imir.ecotrack.dz',
            self::JAGUAR_LIVRAISON => 'https://jaguar.ecotrack.dz',
            self::JO_EXPRESS => 'https://joexpress.ecotrack.dz',
            self::LIHLIH_EXPRESS => 'https://lihlihexpress.ecotrack.dz',
            self::LYNX => 'https://lynx.ecotrack.dz',
            self::MAJOR_EXPRESS => 'https://majorex.ecotrack.dz',
            self::MARS_EXPRESS => 'https://marsexpress.ecotrack.dz',
            self::MAZAYA_LOGISTICS => 'https://mazaya.ecotrack.dz',
            self::MED_EXPRESS => 'https://medexpress.ecotrack.dz',
            self::NAVEX_DELIVERY => 'https://navexdelivery.ecotrack.dz',
            self::OM_COURRIER => 'https://omexpress.ecotrack.dz',
            self::ON_TIME_EXPRESS => 'https://ontime.ecotrack.dz',
            self::OVRED => 'https://ovred.ecotrack.dz',
            self::PDEX => 'https://pdex.ecotrack.dz',
            self::QUICK_DELIVERY => 'https://quickdelivery.ecotrack.dz',
            self::RED_EX => 'https://redex.ecotrack.dz',
            self::RIHAL_EXPRESS => 'https://rihalexpress.ecotrack.dz',
            self::RJ_360 => 'https://rj360express.ecotrack.dz',
            self::RM_EXPRESS => 'https://rmexpress.ecotrack.dz',
            self::RS_EXPRESS => 'https://rsexpress.ecotrack.dz',
            self::RUTA_EXPRESS => 'https://rutaexpress.ecotrack.dz',
            self::SAMEX => 'https://samex.ecotrack.dz',
            self::SBL_EXPRESS => 'https://sbl.ecotrack.dz',
            self::SPEED_MAIL => 'https://speedmail.ecotrack.dz',
            self::SULTAN_COLIS => 'https://sultancolisexpress.ecotrack.dz',
            self::SWIFT_EXPRESS => 'https://swift.ecotrack.dz',
            self::TAWSIL_STAR => 'https://tawsil.ecotrack.dz',
            self::UNIVER_DELIVERY => 'https://univerdelivery.ecotrack.dz',
            self::VITRANS => 'https://vitrans.ecotrack.dz',
            self::WEEWEE_DELIVERY => 'https://weeweedelivery.ecotrack.dz',
            self::WIN_DELIVERY => 'https://windelivery.ecotrack.dz',
            self::ZINYA_TEC => 'https://zinyatec.ecotrack.dz',
        };
    }

    // =========================================================================
    // Metadata
    // =========================================================================

    public function metadata(): ProviderMetadata
    {
        return match ($this) {

            // ---- Yalidine engine ----
            self::YALIDINE => new ProviderMetadata(
                name: 'Yalidine',
                title: 'Yalidine',
                website: 'https://yalidine.com/',
                description: 'Yalidine société de livraison en Algérie offre un service de livraison rapide et sécurisé.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://yalidine.app/app/dev/docs/api/index.php',
                support: 'https://yalidine.com/#contact',
                trackingUrl: 'https://yalidine.com/suivre-un-colis/',
            ),
            self::YALITEC => new ProviderMetadata(
                name: 'Yalitec',
                title: 'Yalitec',
                website: 'https://www.yalitec.com/fr',
                description: 'Yalitec société de livraison en Algérie offre un service de livraison rapide et sécurisé.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://yalitec.me/app/dev/docs/api/index.php',
                support: 'https://www.yalitec.com/fr#contact',
                trackingUrl: null,
            ),
            self::EASY_AND_SPEED => new ProviderMetadata(
                name: 'EasyAndSpeed',
                title: 'Easy and Speed',
                website: 'https://easyandspeed.app',
                description: 'Easy and Speed est une société de livraison en Algérie utilisant le moteur Yalidine.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://easyandspeed.app',
                support: 'https://easyandspeed.app',
                trackingUrl: null,
            ),
            self::ECONOMIQUA => new ProviderMetadata(
                name: 'Economiqua',
                title: 'Economiqua',
                website: 'https://economiqua.app',
                description: 'Economiqua est une société de livraison en Algérie utilisant le moteur Yalidine.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://economiqua.app',
                support: 'https://economiqua.app',
                trackingUrl: null,
            ),
            self::GUEPEX => new ProviderMetadata(
                name: 'GuepexExpress',
                title: 'GuepEx Express',
                website: 'https://guepex.app',
                description: 'GuepEx Express est une société de livraison en Algérie utilisant le moteur Yalidine.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://guepex.app',
                support: 'https://guepex.app',
                trackingUrl: null,
            ),
            self::WE_CAN => new ProviderMetadata(
                name: 'WeCanServices',
                title: 'We Can Services',
                website: 'https://wecanservices.me',
                description: 'We Can Services est une société de livraison en Algérie utilisant le moteur Yalidine.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://wecanservices.me',
                support: 'https://wecanservices.me',
                trackingUrl: null,
            ),

            // ---- Maystro ----
            self::MAYSTRO => new ProviderMetadata(
                name: 'MaystroDelivery',
                title: 'Maystro Delivery',
                website: 'https://maystro-delivery.com/',
                description: 'Maystro Delivery société de livraison en Algérie offre un service de livraison rapide et sécurisé.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://maystro.gitbook.io/maystro-delivery-documentation',
                support: 'https://maystro-delivery.com/ContactUS.html',
                trackingUrl: 'https://maystro-delivery.com/trackingSD.html',
            ),

            // ---- Procolis engine ----
            self::PROCOLIS => new ProviderMetadata(
                name: 'Procolis',
                title: 'Procolis',
                website: 'https://procolis.com',
                description: 'Procolis est une plateforme de gestion de livraison en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://procolis.com',
                support: 'https://procolis.com',
                trackingUrl: null,
            ),
            self::ZREXPRESS => new ProviderMetadata(
                name: 'ZRExpress',
                title: 'ZR Express',
                website: 'https://zrexpress.com',
                description: 'ZRexpress société de livraison en Algérie offre un service de livraison rapide et sécurisé.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://zrexpress.com/ZREXPRESS_WEB/FR/Developpement.awp',
                support: 'https://www.facebook.com/ZRexpresslivraison/',
                trackingUrl: null,
            ),
            self::ABEX => new ProviderMetadata(
                name: 'AbexExpress',
                title: 'ABEX Express',
                website: 'https://abex.dz',
                description: 'ABEX Express est une société de livraison en Algérie utilisant le moteur Procolis.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://procolis.com',
                support: 'https://abex.dz',
                trackingUrl: null,
            ),
            self::COLILOG => new ProviderMetadata(
                name: 'ColilogExpress',
                title: 'Colilog Express',
                website: 'https://colilog.dz',
                description: 'Colilog Express est une société de livraison en Algérie utilisant le moteur Procolis.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://procolis.com',
                support: 'https://colilog.dz',
                trackingUrl: null,
            ),
            self::FLASH_DELIVERY => new ProviderMetadata(
                name: 'FlashDelivery',
                title: 'Flash Delivery',
                website: 'https://flashdelivery.dz',
                description: 'Flash Delivery est une société de livraison en Algérie utilisant le moteur Procolis.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://procolis.com',
                support: 'https://flashdelivery.dz',
                trackingUrl: null,
            ),
            self::LEOPARD => new ProviderMetadata(
                name: 'LeopardExpress',
                title: 'Leopard Express',
                website: 'https://leopardexpress.dz',
                description: 'Leopard Express est une société de livraison en Algérie utilisant le moteur Procolis.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://procolis.com',
                support: 'https://leopardexpress.dz',
                trackingUrl: null,
            ),

            // ---- Standalone ----
            self::ZREXPRESS_NEW => new ProviderMetadata(
                name: 'ZRExpressNew',
                title: 'ZR Express NEW',
                website: 'https://zrexpress.app',
                description: 'La nouvelle plateforme ZR Express — API REST moderne remplaçant l\'ancienne intégration Procolis.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://docs.zrexpress.app/reference/createparcelendpoint',
                support: 'mailto:support@zrexpress.net',
                trackingUrl: null,
            ),
            self::ZIMOU => new ProviderMetadata(
                name: 'ZimouExpress',
                title: 'Zimou Express',
                website: 'https://zimou.express',
                description: 'Zimou Express est un routeur de livraison en Algérie qui dispatche automatiquement les colis vers le meilleur transporteur partenaire.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://zimou.express/api/docs',
                support: 'https://zimou.express',
                trackingUrl: 'https://zimou.express',
            ),

            // ---- Independent native adapters ----
            self::ELOGISTIA => new ProviderMetadata(
                name: 'Elogistia',
                title: 'Elogistia',
                website: 'https://elogistia.com',
                description: 'Elogistia est une plateforme de livraison e-commerce en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://api.elogistia.com',
                support: 'https://elogistia.com',
                trackingUrl: 'https://elogistia.com',
            ),
            self::NEAR_DELIVERY => new ProviderMetadata(
                name: 'NearDelivery',
                title: 'Near Delivery',
                website: 'https://neardelivery.app',
                description: 'Near Delivery est une société de livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://api.neardelivery.app',
                support: 'https://neardelivery.app',
                trackingUrl: 'https://neardelivery.app',
            ),
            self::NOEST => new ProviderMetadata(
                name: 'NoestExpress',
                title: 'Noest Express',
                website: 'https://noest-dz.com',
                description: 'Noest Express est une société de livraison en Algérie offrant des services express et économiques.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://app.noest-dz.com',
                support: 'https://noest-dz.com',
                trackingUrl: 'https://noest-dz.com',
            ),
            self::ECOM_DELIVERY => new ProviderMetadata(
                name: 'EcomDelivery',
                title: 'E-COM Delivery',
                website: 'https://ecom-dz.net',
                description: 'E-COM Delivery est une plateforme de livraison e-commerce en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://ecom-dz.net',
                support: 'https://ecom-dz.net',
                trackingUrl: 'https://ecom-dz.net',
            ),

            // ---- Ecotrack engine — existing ----
            self::ECOTRACK => new ProviderMetadata(
                name: 'Ecotrack',
                title: 'Ecotrack',
                website: 'https://ecotrack.dz',
                description: 'Ecotrack est une plateforme multi-transporteurs pour la livraison en Algérie (DHD, Conexlog/UPS et plus).',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://ecotrack.dz',
                support: 'https://ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::ANDERSON => new ProviderMetadata(
                name: 'AndersonDelivery',
                title: 'Anderson Delivery',
                website: 'https://anderson.ecotrack.dz/',
                description: 'Anderson Delivery est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://anderson.ecotrack.dz/',
                support: 'https://anderson.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::AREEX => new ProviderMetadata(
                name: 'Areex',
                title: 'Areex',
                website: 'https://areex.ecotrack.dz/',
                description: 'Areex est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://areex.ecotrack.dz/',
                support: 'https://areex.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::BA_CONSULT => new ProviderMetadata(
                name: 'BaConsult',
                title: 'BA Consult Express',
                website: 'https://bacexpress.ecotrack.dz/',
                description: 'BA Consult est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://bacexpress.ecotrack.dz/',
                support: 'https://bacexpress.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::CONEXLOG => new ProviderMetadata(
                name: 'Conexlog',
                title: 'Conexlog',
                website: 'https://conexlog-dz.com/',
                description: 'CONEXLOG est le prestataire exclusif des services agréés en Algérie pour le groupe UPS.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://conexlog-dz.com/',
                support: 'https://conexlog-dz.com/contact.php',
                trackingUrl: 'https://conexlog-dz.com/suivi.php',
            ),
            self::COYOTE_EXPRESS => new ProviderMetadata(
                name: 'CoyoteExpress',
                title: 'Coyote Express',
                website: 'https://coyoteexpressdz.ecotrack.dz/',
                description: 'Coyote Express est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://coyoteexpressdz.ecotrack.dz/',
                support: 'https://coyoteexpressdz.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::DHD => new ProviderMetadata(
                name: 'Dhd',
                title: 'DHD',
                website: 'https://dhd-dz.com/',
                description: 'DHD livraison est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://dhd-dz.com/',
                support: 'https://dhd-dz.com/#contact',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::DISTAZERO => new ProviderMetadata(
                name: 'Distazero',
                title: 'Distazero',
                website: 'https://distazero.ecotrack.dz/',
                description: 'Distazero est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://distazero.ecotrack.dz/',
                support: 'https://distazero.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::E48HR => new ProviderMetadata(
                name: 'E48hrLivraison',
                title: '48Hr Livraison',
                website: 'https://48hr.ecotrack.dz/',
                description: '48Hr Livraison est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://48hr.ecotrack.dz/',
                support: 'https://48hr.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::FRETDIRECT => new ProviderMetadata(
                name: 'Fretdirect',
                title: 'FRET.Direct',
                website: 'https://fret.ecotrack.dz/',
                description: 'FRET.Direct est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://fret.ecotrack.dz/',
                support: 'https://fret.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::GOLIVRI => new ProviderMetadata(
                name: 'Golivri',
                title: 'GOLIVRI',
                website: 'https://golivri.ecotrack.dz/',
                description: 'GOLIVRI est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://golivri.ecotrack.dz/',
                support: 'https://golivri.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::MONO_HUB => new ProviderMetadata(
                name: 'MonoHub',
                title: 'Mono Hub',
                website: 'https://mono.ecotrack.dz/',
                description: 'Mono Hub est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://mono.ecotrack.dz/',
                support: 'https://mono.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::MSM_GO => new ProviderMetadata(
                name: 'MsmGo',
                title: 'MSM Go',
                website: 'https://msmgo.ecotrack.dz',
                description: 'MSM Go est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://msmgo.ecotrack.dz',
                support: 'https://msmgo.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::NEGMAR_EXPRESS => new ProviderMetadata(
                name: 'NegmarExpress',
                title: 'Negmar Express',
                website: 'https://negmar.ecotrack.dz/',
                description: 'Negmar Express est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://negmar.ecotrack.dz/',
                support: 'https://negmar.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::PACKERS => new ProviderMetadata(
                name: 'Packers',
                title: 'Packers',
                website: 'https://packers.ecotrack.dz/',
                description: 'Packers est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://packers.ecotrack.dz/',
                support: 'https://packers.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::PREST => new ProviderMetadata(
                name: 'Prest',
                title: 'Prest',
                website: 'https://prest.ecotrack.dz/',
                description: 'Prest est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://prest.ecotrack.dz/',
                support: 'https://prest.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::RB_LIVRAISON => new ProviderMetadata(
                name: 'RbLivraison',
                title: 'RB Livraison',
                website: 'https://rblivraison.ecotrack.dz/',
                description: 'RB Livraison est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://rblivraison.ecotrack.dz/',
                support: 'https://rblivraison.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::REX_LIVRAISON => new ProviderMetadata(
                name: 'RexLivraison',
                title: 'Rex Livraison',
                website: 'https://rex.ecotrack.dz/',
                description: 'Rex Livraison est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://rex.ecotrack.dz/',
                support: 'https://rex.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::ROCKET_DELIVERY => new ProviderMetadata(
                name: 'RocketDelivery',
                title: 'Rocket Delivery',
                website: 'https://rocket.ecotrack.dz/',
                description: 'Rocket Delivery est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://rocket.ecotrack.dz/',
                support: 'https://rocket.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::SALVA_DELIVERY => new ProviderMetadata(
                name: 'SalvaDelivery',
                title: 'Salva Delivery',
                website: 'https://salvadelivery.ecotrack.dz/',
                description: 'Salva Delivery est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://salvadelivery.ecotrack.dz/',
                support: 'https://salvadelivery.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::SPEED_DELIVERY => new ProviderMetadata(
                name: 'SpeedDelivery',
                title: 'Speed Delivery',
                website: 'https://speeddelivery.ecotrack.dz/',
                description: 'Speed Delivery est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://speeddelivery.ecotrack.dz/',
                support: 'https://speeddelivery.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::TSL_EXPRESS => new ProviderMetadata(
                name: 'TslExpress',
                title: 'TSL Express',
                website: 'https://tsl.ecotrack.dz/',
                description: 'TSL Express est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://tsl.ecotrack.dz/',
                support: 'https://tsl.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::WORLDEXPRESS => new ProviderMetadata(
                name: 'Worldexpress',
                title: 'WorldExpress',
                website: 'https://worldexpress.ecotrack.dz/',
                description: 'WorldExpress est une entreprise algérienne opérant dans le secteur de livraison express.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://worldexpress.ecotrack.dz/',
                support: 'https://worldexpress.ecotrack.dz/',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),

            // ---- Ecotrack engine ----
            self::ALANIA_EXPRESS => new ProviderMetadata(
                name: 'AlaniaExpress',
                title: 'Alania Express',
                website: 'https://alania.ecotrack.dz',
                description: 'Alania Express — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://alania.ecotrack.dz',
                support: 'https://alania.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::ALLO_LIVRAISON => new ProviderMetadata(
                name: 'AlloLivraison',
                title: 'Allo Livraison',
                website: 'https://allolivraison.ecotrack.dz',
                description: 'Allo Livraison — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://allolivraison.ecotrack.dz',
                support: 'https://allolivraison.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::AMANA_SPEED => new ProviderMetadata(
                name: 'AmanaSpeed',
                title: 'AMANA Speed',
                website: 'https://amana.ecotrack.dz',
                description: 'AMANA Speed — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://amana.ecotrack.dz',
                support: 'https://amana.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::ARANEX => new ProviderMetadata(
                name: 'Aranex',
                title: 'Aranex',
                website: 'https://aranex.ecotrack.dz',
                description: 'Aranex — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://aranex.ecotrack.dz',
                support: 'https://aranex.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::AREEX_DELIVERY => new ProviderMetadata(
                name: 'AreexDelivery',
                title: 'Areex Delivery',
                website: 'https://areex.ecotrack.dz',
                description: 'Areex Delivery — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://areex.ecotrack.dz',
                support: 'https://areex.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::ATLAS_EXPRESS => new ProviderMetadata(
                name: 'AtlasExpress',
                title: 'Atlas Express',
                website: 'https://atlaexpress.ecotrack.dz',
                description: 'Atlas Express — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://atlaexpress.ecotrack.dz',
                support: 'https://atlaexpress.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::BFK_EXPRESS => new ProviderMetadata(
                name: 'BfkExpress',
                title: 'BFK Express',
                website: 'https://bfkexpress.ecotrack.dz',
                description: 'BFK Express — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://bfkexpress.ecotrack.dz',
                support: 'https://bfkexpress.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::BOOGI => new ProviderMetadata(
                name: 'BoogiTechnologie',
                title: 'Boogi Technologie',
                website: 'https://boogi.ecotrack.dz',
                description: 'Boogi Technologie — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://boogi.ecotrack.dz',
                support: 'https://boogi.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::CHAMPION_LOGISTICS => new ProviderMetadata(
                name: 'ChampionLogistics',
                title: 'Champion Logistics',
                website: 'https://champion.ecotrack.dz',
                description: 'Champion Logistics — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://champion.ecotrack.dz',
                support: 'https://champion.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::CHRONOREX => new ProviderMetadata(
                name: 'ChronorexExpress',
                title: 'Chronorex Express',
                website: 'https://chronorex.ecotrack.dz',
                description: 'Chronorex Express — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://chronorex.ecotrack.dz',
                support: 'https://chronorex.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::CIRTA_EXPRESS => new ProviderMetadata(
                name: 'CirtaExpress',
                title: 'Cirta Express',
                website: 'https://cirtaexpress.ecotrack.dz',
                description: 'Cirta Express — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://cirtaexpress.ecotrack.dz',
                support: 'https://cirtaexpress.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::COLEX => new ProviderMetadata(
                name: 'Colex',
                title: 'Colex',
                website: 'https://colex.ecotrack.dz',
                description: 'Colex — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://colex.ecotrack.dz',
                support: 'https://colex.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::COLIRELI => new ProviderMetadata(
                name: 'ColiReli',
                title: 'ColiReli',
                website: 'https://colireli.ecotrack.dz',
                description: 'ColiReli — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://colireli.ecotrack.dz',
                support: 'https://colireli.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::COLIZONE => new ProviderMetadata(
                name: 'Colizone',
                title: 'Colizone',
                website: 'https://colizone.ecotrack.dz',
                description: 'Colizone — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://colizone.ecotrack.dz',
                support: 'https://colizone.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::DELIVRO_MAIL => new ProviderMetadata(
                name: 'DelivroMail',
                title: 'Delivro Mail',
                website: 'https://delivromail.ecotrack.dz',
                description: 'Delivro Mail — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://delivromail.ecotrack.dz',
                support: 'https://delivromail.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::ECO_RAPIDE => new ProviderMetadata(
                name: 'EcoRapideExpress',
                title: 'Eco Rapide Express',
                website: 'https://ecorapide-express.ecotrack.dz',
                description: 'Eco Rapide Express — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://ecorapide-express.ecotrack.dz',
                support: 'https://ecorapide-express.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::EL_GUIDE => new ProviderMetadata(
                name: 'ElGuideDelivery',
                title: 'El Guide Delivery',
                website: 'https://elguidedelivery.ecotrack.dz',
                description: 'El Guide Delivery — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://elguidedelivery.ecotrack.dz',
                support: 'https://elguidedelivery.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::EXPEDIA_CHRONO => new ProviderMetadata(
                name: 'ExpediaChrono',
                title: 'Expedia Chrono',
                website: 'https://expediachrono.ecotrack.dz',
                description: 'Expedia Chrono — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://expediachrono.ecotrack.dz',
                support: 'https://expediachrono.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::FAST_HORSE => new ProviderMetadata(
                name: 'FastHorseExpress',
                title: 'Fast Horse Express',
                website: 'https://fasthorse.ecotrack.dz',
                description: 'Fast Horse Express — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://fasthorse.ecotrack.dz',
                support: 'https://fasthorse.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::FZ_DELIVERY => new ProviderMetadata(
                name: 'FzDelivery',
                title: 'FZ Delivery',
                website: 'https://fzdelivery.ecotrack.dz',
                description: 'FZ Delivery — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://fzdelivery.ecotrack.dz',
                support: 'https://fzdelivery.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::GS_ECOMMERCE => new ProviderMetadata(
                name: 'GsEcommerce',
                title: 'GS Ecommerce',
                website: 'https://gsecommerce.ecotrack.dz',
                description: 'GS Ecommerce — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://gsecommerce.ecotrack.dz',
                support: 'https://gsecommerce.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::HDD_EXPRESS => new ProviderMetadata(
                name: 'HddExpress',
                title: 'HDD Express',
                website: 'https://hhdexpress.ecotrack.dz',
                description: 'HDD Express — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://hhdexpress.ecotrack.dz',
                support: 'https://hhdexpress.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::IMIR_LOGISTICS => new ProviderMetadata(
                name: 'ImirLogistics',
                title: 'Imir Logistics',
                website: 'https://imir.ecotrack.dz',
                description: 'Imir Logistics — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://imir.ecotrack.dz',
                support: 'https://imir.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::JAGUAR_LIVRAISON => new ProviderMetadata(
                name: 'JaguarLivraison',
                title: 'Jaguar Livraison',
                website: 'https://jaguar.ecotrack.dz',
                description: 'Jaguar Livraison — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://jaguar.ecotrack.dz',
                support: 'https://jaguar.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::JO_EXPRESS => new ProviderMetadata(
                name: 'JoExpress',
                title: 'Jo Express',
                website: 'https://joexpress.ecotrack.dz',
                description: 'Jo Express — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://joexpress.ecotrack.dz',
                support: 'https://joexpress.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::LIHLIH_EXPRESS => new ProviderMetadata(
                name: 'LihlihExpress',
                title: 'LIH LIH EXPRESS',
                website: 'https://lihlihexpress.ecotrack.dz',
                description: 'LIH LIH EXPRESS — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://lihlihexpress.ecotrack.dz',
                support: 'https://lihlihexpress.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::LYNX => new ProviderMetadata(
                name: 'LynxExpress',
                title: 'Lynx Express',
                website: 'https://lynx.ecotrack.dz',
                description: 'Lynx Express — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://lynx.ecotrack.dz',
                support: 'https://lynx.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::MAJOR_EXPRESS => new ProviderMetadata(
                name: 'MajorExpress',
                title: 'Major Express',
                website: 'https://majorex.ecotrack.dz',
                description: 'Major Express — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://majorex.ecotrack.dz',
                support: 'https://majorex.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::MARS_EXPRESS => new ProviderMetadata(
                name: 'MarsExpress',
                title: 'Mars Express',
                website: 'https://marsexpress.ecotrack.dz',
                description: 'Mars Express — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://marsexpress.ecotrack.dz',
                support: 'https://marsexpress.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::MAZAYA_LOGISTICS => new ProviderMetadata(
                name: 'MazayaLogistics',
                title: 'Mazaya Logistics',
                website: 'https://mazaya.ecotrack.dz',
                description: 'Mazaya Logistics — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://mazaya.ecotrack.dz',
                support: 'https://mazaya.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::MED_EXPRESS => new ProviderMetadata(
                name: 'MedExpress',
                title: 'Med Express',
                website: 'https://medexpress.ecotrack.dz',
                description: 'Med Express — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://medexpress.ecotrack.dz',
                support: 'https://medexpress.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::NAVEX_DELIVERY => new ProviderMetadata(
                name: 'NavexDelivery',
                title: 'Navex Delivery',
                website: 'https://navexdelivery.ecotrack.dz',
                description: 'Navex Delivery — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://navexdelivery.ecotrack.dz',
                support: 'https://navexdelivery.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::OM_COURRIER => new ProviderMetadata(
                name: 'OmCourrierExpress',
                title: 'Om Courrier Express',
                website: 'https://omexpress.ecotrack.dz',
                description: 'Om Courrier Express — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://omexpress.ecotrack.dz',
                support: 'https://omexpress.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::ON_TIME_EXPRESS => new ProviderMetadata(
                name: 'OnTimeExpress',
                title: 'On Time Express',
                website: 'https://ontime.ecotrack.dz',
                description: 'On Time Express — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://ontime.ecotrack.dz',
                support: 'https://ontime.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::OVRED => new ProviderMetadata(
                name: 'Ovred',
                title: 'Ovred',
                website: 'https://ovred.ecotrack.dz',
                description: 'Ovred — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://ovred.ecotrack.dz',
                support: 'https://ovred.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::PDEX => new ProviderMetadata(
                name: 'Pdex',
                title: 'PDEX',
                website: 'https://pdex.ecotrack.dz',
                description: 'PDEX — Package Delivery Express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://pdex.ecotrack.dz',
                support: 'https://pdex.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::QUICK_DELIVERY => new ProviderMetadata(
                name: 'QuickDelivery',
                title: 'Quick Delivery DZ',
                website: 'https://quickdelivery.ecotrack.dz',
                description: 'Quick Delivery DZ — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://quickdelivery.ecotrack.dz',
                support: 'https://quickdelivery.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::RED_EX => new ProviderMetadata(
                name: 'RedEx',
                title: 'Red Ex',
                website: 'https://redex.ecotrack.dz',
                description: 'Red Ex — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://redex.ecotrack.dz',
                support: 'https://redex.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::RIHAL_EXPRESS => new ProviderMetadata(
                name: 'RihalExpress',
                title: 'Rihal Express',
                website: 'https://rihalexpress.ecotrack.dz',
                description: 'Rihal Express — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://rihalexpress.ecotrack.dz',
                support: 'https://rihalexpress.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::RJ_360 => new ProviderMetadata(
                name: 'Rj360Express',
                title: 'RJ 360 Express',
                website: 'https://rj360express.ecotrack.dz',
                description: 'RJ 360 Express — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://rj360express.ecotrack.dz',
                support: 'https://rj360express.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::RM_EXPRESS => new ProviderMetadata(
                name: 'RmExpress',
                title: 'RM Express',
                website: 'https://rmexpress.ecotrack.dz',
                description: 'RM Express — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://rmexpress.ecotrack.dz',
                support: 'https://rmexpress.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::RS_EXPRESS => new ProviderMetadata(
                name: 'RsExpress',
                title: 'RS Express',
                website: 'https://rsexpress.ecotrack.dz',
                description: 'RS Express — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://rsexpress.ecotrack.dz',
                support: 'https://rsexpress.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::RUTA_EXPRESS => new ProviderMetadata(
                name: 'RutaExpress',
                title: 'Ruta Express',
                website: 'https://rutaexpress.ecotrack.dz',
                description: 'Ruta Express — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://rutaexpress.ecotrack.dz',
                support: 'https://rutaexpress.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::SAMEX => new ProviderMetadata(
                name: 'Samex',
                title: 'Samex',
                website: 'https://samex.ecotrack.dz',
                description: 'Samex — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://samex.ecotrack.dz',
                support: 'https://samex.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::SBL_EXPRESS => new ProviderMetadata(
                name: 'SblExpress',
                title: 'SBL Express',
                website: 'https://sbl.ecotrack.dz',
                description: 'SBL Express — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://sbl.ecotrack.dz',
                support: 'https://sbl.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::SPEED_MAIL => new ProviderMetadata(
                name: 'SpeedMail',
                title: 'Speed Mail',
                website: 'https://speedmail.ecotrack.dz',
                description: 'Speed Mail — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://speedmail.ecotrack.dz',
                support: 'https://speedmail.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::SULTAN_COLIS => new ProviderMetadata(
                name: 'SultanColisExpress',
                title: 'Sultan Colis Express',
                website: 'https://sultancolisexpress.ecotrack.dz',
                description: 'Sultan Colis Express — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://sultancolisexpress.ecotrack.dz',
                support: 'https://sultancolisexpress.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::SWIFT_EXPRESS => new ProviderMetadata(
                name: 'SwiftExpress',
                title: 'Swift Express',
                website: 'https://swift.ecotrack.dz',
                description: 'Swift Express — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://swift.ecotrack.dz',
                support: 'https://swift.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::TAWSIL_STAR => new ProviderMetadata(
                name: 'TawsilStar',
                title: 'Tawsil Star',
                website: 'https://tawsil.ecotrack.dz',
                description: 'Tawsil Star — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://tawsil.ecotrack.dz',
                support: 'https://tawsil.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::UNIVER_DELIVERY => new ProviderMetadata(
                name: 'UniverDelivery',
                title: 'Univer Delivery',
                website: 'https://univerdelivery.ecotrack.dz',
                description: 'Univer Delivery — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://univerdelivery.ecotrack.dz',
                support: 'https://univerdelivery.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::VITRANS => new ProviderMetadata(
                name: 'Vitrans',
                title: 'Vitrans',
                website: 'https://vitrans.ecotrack.dz',
                description: 'Vitrans — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://vitrans.ecotrack.dz',
                support: 'https://vitrans.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::WEEWEE_DELIVERY => new ProviderMetadata(
                name: 'WeeWeeDelivery',
                title: 'Wee Wee Delivery',
                website: 'https://weeweedelivery.ecotrack.dz',
                description: 'Wee Wee Delivery — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://weeweedelivery.ecotrack.dz',
                support: 'https://weeweedelivery.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::WIN_DELIVERY => new ProviderMetadata(
                name: 'WinDelivery',
                title: 'Win Delivery',
                website: 'https://windelivery.ecotrack.dz',
                description: 'Win Delivery — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://windelivery.ecotrack.dz',
                support: 'https://windelivery.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
            self::ZINYA_TEC => new ProviderMetadata(
                name: 'ZinyaTec',
                title: 'Zinya Tec',
                website: 'https://zinyatec.ecotrack.dz',
                description: 'Zinya Tec — livraison express en Algérie.',
                logo: $this->getLogoUrl(),
                apiDocs: 'https://zinyatec.ecotrack.dz',
                support: 'https://zinyatec.ecotrack.dz',
                trackingUrl: 'https://suivi.ecotrack.dz/suivi/',
            ),
        };
    }
}
