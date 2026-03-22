<?php

declare(strict_types=1);

namespace Uften\Courier\Facades;

use Illuminate\Support\Facades\Facade;
use Uften\Courier\Contracts\ProviderAdapter;
use Uften\Courier\CourierManager;
use Uften\Courier\Data\ProviderMetadata;
use Uften\Courier\Enums\Provider;

/**
 * @method static ProviderAdapter provider(Provider $provider, ?array $credentials = null)
 * @method static ProviderAdapter via(string $providerString, ?array $credentials = null)
 * @method static ProviderMetadata metadataFor(Provider $provider)
 * @method static array<string, ProviderMetadata> allMetadata()
 * @method static CourierManager extend(Provider $provider, \Closure $factory)
 * @method static CourierManager flushResolved()
 *
 * @see CourierManager
 */
final class Courier extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CourierManager::class;
    }
}
