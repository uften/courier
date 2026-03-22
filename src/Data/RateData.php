<?php

declare(strict_types=1);

namespace Uften\Courier\Data;

use Uften\Courier\Enums\DeliveryType;
use Uften\Courier\Enums\Provider;

/**
 * Unified shipping rate entry.
 */
final readonly class RateData
{
    public function __construct(
        public Provider $provider,
        public int $toWilayaId,
        public string $toWilayaName,
        public float $homeDeliveryPrice,
        public float $stopDeskPrice,
        public DeliveryType $deliveryType,
        public ?int $fromWilayaId = null,
        public ?string $fromWilayaName = null,
        public ?int $estimatedDaysMin = null,
        public ?int $estimatedDaysMax = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider->value,
            'from_wilaya_id' => $this->fromWilayaId,
            'from_wilaya_name' => $this->fromWilayaName,
            'to_wilaya_id' => $this->toWilayaId,
            'to_wilaya_name' => $this->toWilayaName,
            'home_delivery_price' => $this->homeDeliveryPrice,
            'stop_desk_price' => $this->stopDeskPrice,
            'delivery_type' => $this->deliveryType->value,
            'estimated_days_min' => $this->estimatedDaysMin,
            'estimated_days_max' => $this->estimatedDaysMax,
        ];
    }
}
