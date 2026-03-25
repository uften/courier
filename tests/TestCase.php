<?php

declare(strict_types=1);

namespace Uften\Courier\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Uften\Courier\CourierServiceProvider;
use Uften\Courier\Enums\Provider;
use Uften\Courier\Facades\Courier;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [CourierServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['Courier' => Courier::class];
    }

    protected function defineEnvironment($app): void
    {
        // Yalidine-engine providers
        foreach ([Provider::YALIDINE, Provider::YALITEC] as $provider) {
            $app['config']->set("courier.providers.{$provider->value}", [
                'token' => 'test-token',
                'key' => 'test-key',
            ]);
        }

        // Single-token providers: Maystro + all Ecotrack-engine providers
        $tokenProviders = array_filter(
            Provider::cases(),
            fn (Provider $p) => $p->isEcotrackEngine() || $p === Provider::MAYSTRO || $p === Provider::ZIMOU,
        );

        foreach ($tokenProviders as $provider) {
            $app['config']->set("courier.providers.{$provider->value}", [
                'token' => 'test-token',
            ]);
        }

        // ZR Express NEW
        $app['config']->set('courier.providers.zrexpress_new', [
            'tenant_id' => 'test-tenant-uuid',
            'api_key' => 'test-api-key',
        ]);

        // Procolis-engine providers
        foreach ([Provider::PROCOLIS, Provider::ZREXPRESS] as $provider) {
            $app['config']->set("courier.providers.{$provider->value}", [
                'id' => 'test-id',
                'token' => 'test-token',
            ]);
        }
    }
}
