<?php

declare(strict_types=1);

use Uften\Courier\Adapters\AbstractAdapter;
use Uften\Courier\Adapters\EcotrackAdapter;
use Uften\Courier\Adapters\MaystroAdapter;
use Uften\Courier\Adapters\ProcolisAdapter;
use Uften\Courier\Adapters\YalidineAdapter;
use Uften\Courier\CourierManager;
use Uften\Courier\Data\CreateOrderData;
use Uften\Courier\Data\LabelData;
use Uften\Courier\Data\OrderData;
use Uften\Courier\Data\ProviderMetadata;
use Uften\Courier\Enums\Provider;
use Uften\Courier\Exceptions\InvalidCredentialsConfigException;
use Uften\Courier\Exceptions\UnsupportedOperationException;
use Uften\Courier\Facades\Courier;

describe('CourierManager', function (): void {

    it('is registered in the service container', function (): void {
        expect(app(CourierManager::class))->toBeInstanceOf(CourierManager::class);
    });

    it('resolves the Courier facade to a CourierManager', function (): void {
        expect(Courier::getFacadeRoot())->toBeInstanceOf(CourierManager::class);
    });

    // -------------------------------------------------------------------------
    // Adapter resolution for all engine types
    // -------------------------------------------------------------------------

    it('resolves YalidineAdapter for Provider::YALIDINE', function (): void {
        expect(Courier::provider(Provider::YALIDINE))->toBeInstanceOf(YalidineAdapter::class);
    });

    it('resolves YalidineAdapter for Provider::YALITEC with Yalitec base URL', function (): void {
        $adapter = Courier::provider(Provider::YALITEC);
        expect($adapter)->toBeInstanceOf(YalidineAdapter::class)
            ->and($adapter->provider())->toBe(Provider::YALITEC);
    });

    it('resolves MaystroAdapter for Provider::MAYSTRO', function (): void {
        expect(Courier::provider(Provider::MAYSTRO))->toBeInstanceOf(MaystroAdapter::class);
    });

    it('resolves ProcolisAdapter for PROCOLIS and ZREXPRESS', function (): void {
        expect(Courier::provider(Provider::PROCOLIS))->toBeInstanceOf(ProcolisAdapter::class)
            ->and(Courier::provider(Provider::ZREXPRESS))->toBeInstanceOf(ProcolisAdapter::class);
    });

    it('resolves EcotrackAdapter for the generic ECOTRACK provider', function (): void {
        expect(Courier::provider(Provider::ECOTRACK))->toBeInstanceOf(EcotrackAdapter::class);
    });

    it('resolves EcotrackAdapter for all 22 Ecotrack sub-providers with correct provider enum', function (): void {
        $subProviders = [
            Provider::DHD,
            Provider::CONEXLOG,
            Provider::ANDERSON,
            Provider::AREEX,
            Provider::BA_CONSULT,
            Provider::COYOTE_EXPRESS,
            Provider::DISTAZERO,
            Provider::E48HR,
            Provider::FRETDIRECT,
            Provider::GOLIVRI,
            Provider::MONO_HUB,
            Provider::MSM_GO,
            Provider::NEGMAR_EXPRESS,
            Provider::PACKERS,
            Provider::PREST,
            Provider::RB_LIVRAISON,
            Provider::REX_LIVRAISON,
            Provider::ROCKET_DELIVERY,
            Provider::SALVA_DELIVERY,
            Provider::SPEED_DELIVERY,
            Provider::TSL_EXPRESS,
            Provider::WORLDEXPRESS,
        ];

        foreach ($subProviders as $provider) {
            $adapter = Courier::provider($provider);
            expect($adapter)->toBeInstanceOf(EcotrackAdapter::class)
                ->and($adapter->provider())->toBe($provider, "Wrong provider on {$provider->value}");
        }
    });

    // -------------------------------------------------------------------------
    // Metadata access (no adapter instantiation needed)
    // -------------------------------------------------------------------------

    it('metadataFor() returns correct metadata without instantiating an adapter', function (): void {
        $meta = Courier::metadataFor(Provider::DHD);
        expect($meta)->toBeInstanceOf(ProviderMetadata::class)
            ->and($meta->title)->toBe('DHD')
            ->and($meta->website)->toBe('https://dhd-dz.com/');
    });

    it('allMetadata() returns 30 entries', function (): void {
        expect(Courier::allMetadata())->toHaveCount(30);
    });

    it('adapter metadata() delegates to Provider enum', function (): void {
        $adapter = Courier::provider(Provider::CONEXLOG);
        $meta = $adapter->metadata();

        expect($meta->title)->toBe('Conexlog')
            ->and($meta->trackingUrl)->toBe('https://conexlog-dz.com/suivi.php');
    });

    // -------------------------------------------------------------------------
    // Other manager features
    // -------------------------------------------------------------------------

    it('caches resolved adapters by provider key', function (): void {
        Courier::flushResolved();
        $a = Courier::provider(Provider::DHD);
        $b = Courier::provider(Provider::DHD);
        expect($a)->toBe($b);
    });

    it('resolves by string via ::via()', function (): void {
        expect(Courier::via('dhd'))->toBeInstanceOf(EcotrackAdapter::class)
            ->and(Courier::via('yalitec'))->toBeInstanceOf(YalidineAdapter::class);
    });

    it('accepts runtime credentials that override config', function (): void {
        $adapter = Courier::provider(Provider::YALIDINE, ['token' => 'rt', 'key' => 'rk']);
        expect($adapter)->toBeInstanceOf(YalidineAdapter::class);
    });

    it('throws InvalidCredentialsConfigException when token is missing', function (): void {
        $manager = new CourierManager(['providers' => ['dhd' => []]]);
        expect(fn () => $manager->provider(Provider::DHD))
            ->toThrow(InvalidCredentialsConfigException::class);
    });

    it('allows custom adapter drivers via extend()', function (): void {
        $fake = new class extends AbstractAdapter
        {
            public function __construct()
            {
                parent::__construct('https://fake.test');
                $this->providerEnum = Provider::DHD;
            }

            public function testCredentials(): bool
            {
                return true;
            }

            public function getCreateOrderValidationRules(): array
            {
                return [];
            }

            public function createOrder(CreateOrderData $data): OrderData
            {
                throw new UnsupportedOperationException('createOrder', $this->providerEnum);
            }

            public function getOrder(string $t): OrderData
            {
                throw new UnsupportedOperationException('getOrder', $this->providerEnum);
            }

            public function getLabel(string $t): LabelData
            {
                throw new UnsupportedOperationException('getLabel', $this->providerEnum);
            }
        };

        Courier::extend(Provider::DHD, fn () => $fake);
        Courier::flushResolved();

        expect(Courier::provider(Provider::DHD))->toBe($fake);

        Courier::clearResolvedInstance('courier');
    });

    it('has correct config for zrexpress', function (): void {
        expect(config('courier.providers.zrexpress'))->toBe([
            'id' => 'test-id',
            'token' => 'test-token',
        ]);
    });
});
