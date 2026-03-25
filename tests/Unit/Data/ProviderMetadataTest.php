<?php

declare(strict_types=1);

use Uften\Courier\CourierManager;
use Uften\Courier\Data\ProviderMetadata;
use Uften\Courier\Enums\Provider;

describe('ProviderMetadata DTO', function (): void {

    it('constructs correctly with all fields', function (): void {
        $meta = new ProviderMetadata(
            name: 'TestProvider',
            title: 'Test Provider',
            website: 'https://test.example.com',
            description: 'A test provider.',
            logo: 'https://test.example.com/logo.png',
            apiDocs: 'https://test.example.com/docs',
            support: 'https://test.example.com/support',
            trackingUrl: 'https://test.example.com/track',
        );

        expect($meta->name)->toBe('TestProvider')
            ->and($meta->title)->toBe('Test Provider')
            ->and($meta->logo)->toBe('https://test.example.com/logo.png')
            ->and($meta->trackingUrl)->toBe('https://test.example.com/track');
    });

    it('allows null optional fields', function (): void {
        $meta = new ProviderMetadata(
            name: 'Minimal', title: 'Minimal', website: 'https://min.dz', description: 'desc',
        );

        expect($meta->logo)->toBeNull()
            ->and($meta->apiDocs)->toBeNull()
            ->and($meta->trackingUrl)->toBeNull();
    });

    it('fromArray strips "#" placeholder logos to null', function (): void {
        $meta = ProviderMetadata::fromArray([
            'name' => 'Areex', 'title' => 'Areex',
            'website' => 'https://areex.ecotrack.dz/',
            'description' => 'desc',
            'logo' => '#',
        ]);

        expect($meta->logo)->toBeNull();
    });

    it('fromArray preserves real logo URLs', function (): void {
        $meta = ProviderMetadata::fromArray([
            'name' => 'DHD', 'title' => 'DHD',
            'website' => 'https://dhd-dz.com/',
            'description' => 'desc',
            'logo' => 'https://dhd-dz.com/assets/img/logo.png',
        ]);

        expect($meta->logo)->toBe('https://dhd-dz.com/assets/img/logo.png');
    });

    it('toArray round-trips correctly', function (): void {
        $meta = Provider::DHD->metadata();
        $array = $meta->toArray();

        expect($array)->toHaveKey('name', 'Dhd')
            ->and($array)->toHaveKey('title', 'DHD')
            ->and($array)->toHaveKey('website', 'https://dhd-dz.com/')
            ->and($array)->toHaveKey('tracking_url', 'https://suivi.ecotrack.dz/suivi/');
    });

    it('every Ecotrack sub-provider has tracking_url pointing to suivi.ecotrack.dz', function (): void {
        $ecotrackSubs = array_filter(
            Provider::cases(),
            fn (Provider $p) => $p->isEcotrackEngine() && $p !== Provider::ECOTRACK,
        );

        foreach ($ecotrackSubs as $provider) {
            $meta = $provider->metadata();
            // Conexlog is the exception — it has its own tracking URL
            if ($provider === Provider::CONEXLOG) {
                expect($meta->trackingUrl)->toContain('conexlog');
            } else {
                expect($meta->trackingUrl)
                    ->toContain('suivi.ecotrack.dz');
            }
        }
    });

    it('metadata is accessible via the ProviderAdapter interface after adapter instantiation', function (): void {
        // The TestCase wires credentials in defineEnvironment()
        $adapter = app(CourierManager::class)->provider(Provider::DHD);

        $meta = $adapter->metadata();

        expect($meta)->toBeInstanceOf(ProviderMetadata::class)
            ->and($meta->name)->toBe('Dhd')
            ->and($meta->title)->toBe('DHD');
    });

    it('metadataFor() on CourierManager returns correct metadata without instantiating adapter', function (): void {
        $meta = app(CourierManager::class)->metadataFor(Provider::CONEXLOG);

        expect($meta->title)->toBe('Conexlog')
            ->and($meta->website)->toBe('https://conexlog-dz.com/');
    });

    it('allMetadata() returns entries for all 30 providers', function (): void {
        $all = app(CourierManager::class)->allMetadata();

        expect($all)->toHaveCount(30)
            ->and($all)->toHaveKey('yalidine')
            ->and($all)->toHaveKey('dhd')
            ->and($all)->toHaveKey('conexlog')
            ->and($all)->toHaveKey('anderson')
            ->and($all)->toHaveKey('worldexpress');
    });

});
