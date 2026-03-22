<?php

declare(strict_types=1);

namespace Uften\Courier;

use Illuminate\Support\ServiceProvider;

final class CourierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/courier.php',
            'courier',
        );

        $this->app->singleton(CourierManager::class, function ($app): CourierManager {
            return new CourierManager(
                config: $app['config']->get('courier', []),
            );
        });

        // Allow resolving by the interface too
        $this->app->alias(CourierManager::class, 'courier');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/courier.php' => config_path('courier.php'),
            ], 'courier-config');
        }
    }

    /**
     * @return list<string>
     */
    public function provides(): array
    {
        return [
            CourierManager::class,
            'courier',
        ];
    }
}
