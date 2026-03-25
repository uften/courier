<?php

declare(strict_types=1);

use Uften\Courier\Adapters\EcotrackAdapter;
use Uften\Courier\Adapters\MaystroAdapter;
use Uften\Courier\Adapters\ProcolisAdapter;
use Uften\Courier\Adapters\YalidineAdapter;
use Uften\Courier\Data\ProviderMetadata;
use Uften\Courier\Enums\Provider;

describe('Provider enum', function (): void {

    it('has 30 total cases', function (): void {
        expect(Provider::cases())->toHaveCount(30);
    });

    it('has correct backing values for key providers', function (): void {
        expect(Provider::YALIDINE->value)->toBe('yalidine')
            ->and(Provider::YALITEC->value)->toBe('yalitec')
            ->and(Provider::MAYSTRO->value)->toBe('maystro')
            ->and(Provider::PROCOLIS->value)->toBe('procolis')
            ->and(Provider::ZREXPRESS->value)->toBe('zrexpress')
            ->and(Provider::ZREXPRESS_NEW->value)->toBe('zrexpress_new')
            ->and(Provider::ECOTRACK->value)->toBe('ecotrack')
            ->and(Provider::DHD->value)->toBe('dhd')
            ->and(Provider::CONEXLOG->value)->toBe('conexlog');
    });

    it('maps Yalidine engine providers to YalidineAdapter', function (): void {
        expect(Provider::YALIDINE->adapterClass())->toBe(YalidineAdapter::class)
            ->and(Provider::YALITEC->adapterClass())->toBe(YalidineAdapter::class);
    });

    it('maps all Ecotrack-engine providers to EcotrackAdapter', function (): void {
        $ecotrackProviders = array_filter(
            Provider::cases(),
            fn (Provider $p) => $p->isEcotrackEngine(),
        );

        expect($ecotrackProviders)->not->toBeEmpty();

        foreach ($ecotrackProviders as $provider) {
            expect($provider->adapterClass())
                ->toBe(EcotrackAdapter::class, "Expected {$provider->value} → EcotrackAdapter");
        }
    });

    it('maps Maystro to MaystroAdapter', function (): void {
        expect(Provider::MAYSTRO->adapterClass())->toBe(MaystroAdapter::class);
    });

    it('maps Procolis engine providers to ProcolisAdapter', function (): void {
        expect(Provider::PROCOLIS->adapterClass())->toBe(ProcolisAdapter::class)
            ->and(Provider::ZREXPRESS->adapterClass())->toBe(ProcolisAdapter::class);
    });

    it('provides a non-empty HTTPS base URL for every provider', function (Provider $provider): void {
        expect($provider->baseUrl())
            ->toBeString()
            ->toStartWith('https://');
    })->with(function () {
        foreach (Provider::cases() as $case) {
            yield $case->name => [$case];
        }
    });

    it('flags only Procolis and ZRExpress as requiring an API id', function (): void {
        expect(Provider::PROCOLIS->requiresApiId())->toBeTrue()
            ->and(Provider::ZREXPRESS->requiresApiId())->toBeTrue()
            ->and(Provider::YALIDINE->requiresApiId())->toBeFalse()
            ->and(Provider::DHD->requiresApiId())->toBeFalse()
            ->and(Provider::MAYSTRO->requiresApiId())->toBeFalse();
    });

    it('returns a ProviderMetadata instance for every provider', function (Provider $provider): void {
        $meta = $provider->metadata();
        expect($meta)->toBeInstanceOf(ProviderMetadata::class)
            ->and($meta->name)->not->toBeEmpty()
            ->and($meta->title)->not->toBeEmpty()
            ->and($meta->website)->toStartWith('https://');
    })->with(function () {
        foreach (Provider::cases() as $case) {
            yield $case->name => [$case];
        }
    });

    it('label() returns the metadata title', function (): void {
        expect(Provider::YALIDINE->label())->toBe('Yalidine')
            ->and(Provider::DHD->label())->toBe('DHD')
            ->and(Provider::CONEXLOG->label())->toBe('Conexlog')
            ->and(Provider::YALITEC->label())->toBe('Yalitec');
    });

    it('isYalidineEngine() is true only for Yalidine and Yalitec', function (): void {
        expect(Provider::YALIDINE->isYalidineEngine())->toBeTrue()
            ->and(Provider::YALITEC->isYalidineEngine())->toBeTrue()
            ->and(Provider::DHD->isYalidineEngine())->toBeFalse()
            ->and(Provider::MAYSTRO->isYalidineEngine())->toBeFalse();
    });

    it('isEcotrackEngine() is true for all 23 Ecotrack providers', function (): void {
        $ecotrackEngineProviders = [
            Provider::ECOTRACK, Provider::ANDERSON, Provider::AREEX,
            Provider::BA_CONSULT, Provider::CONEXLOG, Provider::COYOTE_EXPRESS,
            Provider::DHD, Provider::DISTAZERO, Provider::E48HR,
            Provider::FRETDIRECT, Provider::GOLIVRI, Provider::MONO_HUB,
            Provider::MSM_GO, Provider::NEGMAR_EXPRESS, Provider::PACKERS,
            Provider::PREST, Provider::RB_LIVRAISON, Provider::REX_LIVRAISON,
            Provider::ROCKET_DELIVERY, Provider::SALVA_DELIVERY,
            Provider::SPEED_DELIVERY, Provider::TSL_EXPRESS, Provider::WORLDEXPRESS,
        ];

        expect($ecotrackEngineProviders)->toHaveCount(23);

        foreach ($ecotrackEngineProviders as $provider) {
            expect($provider->isEcotrackEngine())
                ->toBeTrue("Expected {$provider->value} to be Ecotrack engine");
        }
    });

    it('can be resolved from string value', function (): void {
        expect(Provider::from('yalidine'))->toBe(Provider::YALIDINE)
            ->and(Provider::from('dhd'))->toBe(Provider::DHD)
            ->and(Provider::from('conexlog'))->toBe(Provider::CONEXLOG);
    });

});
