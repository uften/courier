<?php

declare(strict_types=1);

namespace Uften\Courier;

use Uften\Courier\Adapters\EcotrackAdapter;
use Uften\Courier\Adapters\MaystroAdapter;
use Uften\Courier\Adapters\ProcolisAdapter;
use Uften\Courier\Adapters\YalidineAdapter;
use Uften\Courier\Adapters\ZimouAdapter;
use Uften\Courier\Contracts\ProviderAdapter;
use Uften\Courier\Data\Credentials\ProcolisCredentials;
use Uften\Courier\Data\Credentials\TokenCredentials;
use Uften\Courier\Data\Credentials\YalidineCredentials;
use Uften\Courier\Data\ProviderMetadata;
use Uften\Courier\Enums\Provider;
use Uften\Courier\Exceptions\InvalidCredentialsConfigException;

/**
 * CourierManager resolves the correct adapter for a given provider,
 * hydrates credentials from config or runtime values, and caches
 * instances for re-use within the same request lifecycle.
 *
 * All 28 Algerian courier providers are supported. Ecotrack-engine
 * sub-providers (DHD, Conexlog, Anderson, etc.) reuse EcotrackAdapter
 * with provider-specific base URLs driven by the Provider enum.
 */
final class CourierManager
{
    /** @var array<string, ProviderAdapter> */
    private array $resolved = [];

    /** @var array<string, \Closure> */
    private array $customDrivers = [];

    public function __construct(
        /** @var array<string, array<string, mixed>> */
        private readonly array $config,
    ) {}

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Resolve an adapter by Provider enum.
     *
     * @param  array<string, string>|null  $credentials  If null, reads from config.
     */
    public function provider(Provider $provider, ?array $credentials = null): ProviderAdapter
    {
        $cacheKey = $provider->value.($credentials ? ':'.md5(serialize($credentials)) : '');

        if (isset($this->resolved[$cacheKey])) {
            return $this->resolved[$cacheKey];
        }

        if (isset($this->customDrivers[$provider->value])) {
            return $this->resolved[$cacheKey] = ($this->customDrivers[$provider->value])($credentials);
        }

        return $this->resolved[$cacheKey] = $this->make($provider, $credentials);
    }

    /**
     * Resolve by the string value of the Provider enum.
     *
     * @param  array<string, string>|null  $credentials
     */
    public function via(string $providerString, ?array $credentials = null): ProviderAdapter
    {
        return $this->provider(Provider::from($providerString), $credentials);
    }

    /**
     * Get static metadata for a provider without instantiating an adapter.
     * Useful for building provider-selection UIs.
     */
    public function metadataFor(Provider $provider): ProviderMetadata
    {
        return $provider->metadata();
    }

    /**
     * Get metadata for all providers.
     *
     * @return array<string, ProviderMetadata> keyed by Provider::value
     */
    public function allMetadata(): array
    {
        $result = [];
        foreach (Provider::cases() as $provider) {
            $result[$provider->value] = $provider->metadata();
        }

        return $result;
    }

    /**
     * Register a custom adapter factory for a given provider.
     *
     * ```php
     * Courier::extend(Provider::DHD, fn(?array $creds) => new MyDhdAdapter($creds));
     * ```
     */
    public function extend(Provider $provider, \Closure $factory): self
    {
        $this->customDrivers[$provider->value] = $factory;
        unset($this->resolved[$provider->value]);

        return $this;
    }

    /** Flush the resolved adapter cache (useful in tests). */
    public function flushResolved(): self
    {
        $this->resolved = [];

        return $this;
    }

    // -------------------------------------------------------------------------
    // Internal factory
    // -------------------------------------------------------------------------

    private function make(Provider $provider, ?array $runtimeCredentials): ProviderAdapter
    {
        $creds = $runtimeCredentials ?? $this->credentialsFromConfig($provider);

        return match (true) {

            // Yalidine engine — Yalidine and Yalitec
            $provider->isYalidineEngine() => new YalidineAdapter(
                credentials: $this->buildYalidineCredentials($provider, $creds),
                provider: $provider,
            ),

            // Maystro (standalone)
            $provider === Provider::MAYSTRO => new MaystroAdapter(
                credentials: $this->buildTokenCredentials($provider, $creds),
            ),

            // Procolis engine — Procolis and ZR Express
            $provider === Provider::PROCOLIS || $provider === Provider::ZREXPRESS => new ProcolisAdapter(
                credentials: $this->buildProcolisCredentials($provider, $creds),
                resolvedProvider: $provider,
            ),

            // Zimou Express (standalone router)
            $provider === Provider::ZIMOU => new ZimouAdapter(
                credentials: $this->buildTokenCredentials($provider, $creds),
            ),

            // Ecotrack engine — generic base + all 22 branded sub-providers
            $provider->isEcotrackEngine() => new EcotrackAdapter(
                credentials: $this->buildTokenCredentials($provider, $creds),
                provider: $provider,
            ),

            // Unreachable — all Provider cases are covered above
            default => throw new \LogicException(
                "No adapter factory defined for provider [{$provider->value}].",
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Config helpers
    // -------------------------------------------------------------------------

    /** @return array<string, string> */
    private function credentialsFromConfig(Provider $provider): array
    {
        $key = $provider->value;

        $providerConfig = $this->config['providers'][$key]
            ?? $this->config[$key]
            ?? [];

        if (! is_array($providerConfig)) {
            throw new InvalidCredentialsConfigException(
                $provider,
                "No configuration found under courier.providers.{$key}.",
            );
        }

        return $providerConfig;
    }

    // -------------------------------------------------------------------------
    // Credential builders
    // -------------------------------------------------------------------------

    /** @param array<string, string> $creds */
    private function buildYalidineCredentials(Provider $provider, array $creds): YalidineCredentials
    {
        try {
            return YalidineCredentials::fromArray($creds);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidCredentialsConfigException($provider, $e->getMessage(), $e);
        }
    }

    /** @param array<string, string> $creds */
    private function buildTokenCredentials(Provider $provider, array $creds): TokenCredentials
    {
        try {
            return TokenCredentials::fromArray($creds);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidCredentialsConfigException($provider, $e->getMessage(), $e);
        }
    }

    /** @param array<string, string> $creds */
    private function buildProcolisCredentials(Provider $provider, array $creds): ProcolisCredentials
    {
        try {
            return ProcolisCredentials::fromArray($creds);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidCredentialsConfigException($provider, $e->getMessage(), $e);
        }
    }
}
