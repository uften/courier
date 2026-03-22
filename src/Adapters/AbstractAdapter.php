<?php

declare(strict_types=1);

namespace Uften\Courier\Adapters;

use Carbon\CarbonImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Uften\Courier\Contracts\ProviderAdapter;
use Uften\Courier\Contracts\StatusNormalizer;
use Uften\Courier\Data\ProviderMetadata;
use Uften\Courier\Enums\Provider;
use Uften\Courier\Enums\TrackingStatus;
use Uften\Courier\Exceptions\AuthenticationException;
use Uften\Courier\Exceptions\CourierException;
use Uften\Courier\Exceptions\UnsupportedOperationException;

/**
 * Abstract base for all courier adapters.
 *
 * Provides:
 *  - A pre-configured Guzzle HTTP client (injectable for testing).
 *  - Shared helpers: get(), post(), put(), delete().
 *  - Uniform HTTP error → exception translation.
 *  - Default metadata() delegating to the Provider enum.
 *  - Default "unsupported" stubs for optional operations.
 */
abstract class AbstractAdapter implements ProviderAdapter, StatusNormalizer
{
    protected readonly Client $http;

    /** Set by each concrete adapter's constructor. */
    protected Provider $providerEnum;

    public function __construct(
        protected readonly string $baseUrl,
        protected readonly array $defaultHeaders = [],
        protected readonly int $timeoutSeconds = 30,
        ?Client $httpClient = null,
    ) {
        $this->http = $httpClient ?? new Client([
            'base_uri' => rtrim($this->baseUrl, '/').'/',
            'timeout' => $this->timeoutSeconds,
            'connect_timeout' => 10,
            RequestOptions::HEADERS => array_merge(
                ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
                $this->defaultHeaders,
            ),
            RequestOptions::HTTP_ERRORS => false,
        ]);
    }

    // -------------------------------------------------------------------------
    // ProviderAdapter: identity
    // -------------------------------------------------------------------------

    public function provider(): Provider
    {
        return $this->providerEnum;
    }

    /**
     * Delegates to the Provider enum — metadata is always available without
     * hitting any API.
     */
    public function metadata(): ProviderMetadata
    {
        return $this->providerEnum->metadata();
    }

    // -------------------------------------------------------------------------
    // ProviderAdapter: optional-operation stubs
    // -------------------------------------------------------------------------

    public function getRates(?int $fromWilayaId = null, ?int $toWilayaId = null): array
    {
        throw new UnsupportedOperationException('getRates', $this->providerEnum);
    }

    public function cancelOrder(string $trackingNumber): bool
    {
        throw new UnsupportedOperationException('cancelOrder', $this->providerEnum);
    }

    // -------------------------------------------------------------------------
    // StatusNormalizer: default fallback
    // -------------------------------------------------------------------------

    public function normalizeStatus(string $rawStatus): TrackingStatus
    {
        return TrackingStatus::UNKNOWN;
    }

    // -------------------------------------------------------------------------
    // HTTP helpers
    // -------------------------------------------------------------------------

    /** @return array<string, mixed> */
    protected function get(string $path, array $query = [], array $headers = []): array
    {
        return $this->request('GET', $path, [
            RequestOptions::QUERY => $query,
            RequestOptions::HEADERS => $headers,
        ]);
    }

    /** @return array<string, mixed> */
    protected function post(string $path, array $payload = [], array $headers = []): array
    {
        return $this->request('POST', $path, [
            RequestOptions::JSON => $payload,
            RequestOptions::HEADERS => $headers,
        ]);
    }

    /** @return array<string, mixed> */
    protected function postForm(string $path, array $payload = [], array $headers = []): array
    {
        return $this->request('POST', $path, [
            RequestOptions::FORM_PARAMS => $payload,
            RequestOptions::HEADERS => $headers,
        ]);
    }

    /** @return array<string, mixed> */
    protected function put(string $path, array $payload = [], array $headers = []): array
    {
        return $this->request('PUT', $path, [
            RequestOptions::JSON => $payload,
            RequestOptions::HEADERS => $headers,
        ]);
    }

    /** @return array<string, mixed> */
    protected function delete(string $path, array $headers = []): array
    {
        return $this->request('DELETE', $path, [
            RequestOptions::HEADERS => $headers,
        ]);
    }

    // -------------------------------------------------------------------------
    // Raw request + error translation
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     *
     * @throws AuthenticationException
     * @throws CourierException
     */
    protected function request(string $method, string $path, array $options = []): array
    {
        try {
            $response = $this->http->request($method, ltrim($path, '/'), $options);
        } catch (\Throwable $e) {
            throw new CourierException(
                "Error communicating with {$this->providerEnum->label()}: {$e->getMessage()}",
                0,
                $e,
            );
        }

        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($statusCode === 401 || $statusCode === 403) {
            throw new AuthenticationException(
                "Authentication failed for {$this->providerEnum->label()} (HTTP {$statusCode}).",
                $statusCode,
            );
        }

        if ($statusCode >= 400) {
            throw new CourierException(
                "{$this->providerEnum->label()} API returned HTTP {$statusCode}: {$body}",
                $statusCode,
            );
        }

        if ($body === '' || $body === 'null') {
            return [];
        }

        $decoded = json_decode($body, associative: true, flags: JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : ['data' => $decoded];
    }

    /**
     * Fetch raw bytes (used for PDF label responses that aren't JSON).
     *
     * @param  array<string, mixed>  $options
     */
    protected function requestRaw(string $method, string $path, array $options = []): string
    {
        try {
            $response = $this->http->request($method, ltrim($path, '/'), $options);
        } catch (\Throwable $e) {
            throw new CourierException(
                "Error communicating with {$this->providerEnum->label()}: {$e->getMessage()}",
                0,
                $e,
            );
        }

        $statusCode = $response->getStatusCode();

        if ($statusCode === 401 || $statusCode === 403) {
            throw new AuthenticationException(
                "Authentication failed for {$this->providerEnum->label()} (HTTP {$statusCode}).",
                $statusCode,
            );
        }

        if ($statusCode >= 400) {
            throw new CourierException(
                "{$this->providerEnum->label()} API returned HTTP {$statusCode}.",
                $statusCode,
            );
        }

        return (string) $response->getBody();
    }

    // -------------------------------------------------------------------------
    // Utilities
    // -------------------------------------------------------------------------

    /** Safe nested array key access. */
    protected function dig(array $data, string ...$keys): mixed
    {
        $current = $data;
        foreach ($keys as $key) {
            if (! is_array($current) || ! array_key_exists($key, $current)) {
                return null;
            }
            $current = $current[$key];
        }

        return $current;
    }

    protected function parseDate(?string $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
