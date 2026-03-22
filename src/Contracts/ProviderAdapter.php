<?php

declare(strict_types=1);

namespace Uften\Courier\Contracts;

use Illuminate\Validation\ValidationException;
use Uften\Courier\Data\CreateOrderData;
use Uften\Courier\Data\LabelData;
use Uften\Courier\Data\OrderData;
use Uften\Courier\Data\ProviderMetadata;
use Uften\Courier\Data\RateData;
use Uften\Courier\Enums\Provider;
use Uften\Courier\Exceptions\AuthenticationException;
use Uften\Courier\Exceptions\CourierException;
use Uften\Courier\Exceptions\OrderNotFoundException;
use Uften\Courier\Exceptions\UnsupportedOperationException;

/**
 * Every courier adapter must implement this interface.
 * The contract enforces a uniform surface so calling code never needs to know
 * which provider it is talking to.
 */
interface ProviderAdapter
{
    /** Returns which provider enum value this adapter belongs to. */
    public function provider(): Provider;

    /**
     * Returns static metadata (name, logo, URLs) for this provider.
     * Useful for building provider-selection UIs without instantiating adapters.
     */
    public function metadata(): ProviderMetadata;

    /**
     * Verify that the configured credentials are valid.
     *
     * @throws AuthenticationException
     */
    public function testCredentials(): bool;

    /**
     * Get shipping rates.
     *
     * @param  int|null  $fromWilayaId  Origin wilaya (1-58). Some providers ignore this.
     * @param  int|null  $toWilayaId  Destination wilaya (1-58). Some providers require this.
     * @return list<RateData>
     *
     * @throws UnsupportedOperationException
     * @throws CourierException
     */
    public function getRates(?int $fromWilayaId = null, ?int $toWilayaId = null): array;

    /**
     * Return the provider-specific Laravel validation rules for order creation.
     *
     * @return array<string, mixed>
     */
    public function getCreateOrderValidationRules(): array;

    /**
     * Create a new shipping order.
     *
     * @throws ValidationException
     * @throws CourierException
     */
    public function createOrder(CreateOrderData $data): OrderData;

    /**
     * Retrieve an existing order by its tracking number.
     *
     * @throws OrderNotFoundException
     * @throws UnsupportedOperationException
     * @throws CourierException
     */
    public function getOrder(string $trackingNumber): OrderData;

    /**
     * Cancel an existing order.
     *
     * @throws UnsupportedOperationException
     * @throws CourierException
     */
    public function cancelOrder(string $trackingNumber): bool;

    /**
     * Retrieve the shipping label for a given tracking number.
     *
     * @throws UnsupportedOperationException
     * @throws CourierException
     */
    public function getLabel(string $trackingNumber): LabelData;
}
