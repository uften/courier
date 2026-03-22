<?php

declare(strict_types=1);

namespace Uften\Courier\Data;

use Carbon\CarbonImmutable;
use Uften\Courier\Enums\Provider;
use Uften\Courier\Enums\TrackingStatus;

/**
 * Unified order / shipment response.
 *
 * Regardless of the provider, getOrder() and createOrder() always return
 * this identical shape so application code remains provider-agnostic.
 */
final readonly class OrderData
{
    public function __construct(
        /** Our internal order reference. */
        public string $orderId,

        /** The provider's own tracking / parcel number. */
        public string $trackingNumber,

        /** Which provider issued this data. */
        public Provider $provider,

        /** Canonical, normalised delivery status. */
        public TrackingStatus $status,

        /** Recipient full name. */
        public string $recipientName,

        /** Recipient phone. */
        public string $phone,

        /** Delivery address. */
        public string $address,

        /** Destination wilaya ID. */
        public int $toWilayaId,

        /** Destination commune name. */
        public string $toCommune,

        /** Cash-on-delivery amount in DZD. */
        public float $price,

        /** Shipping fee in DZD (if known). */
        public ?float $shippingFee = null,

        /** Raw, untransformed status string from the provider. */
        public ?string $rawStatus = null,

        /** Additional notes from the provider. */
        public ?string $notes = null,

        /** When the order was first created at the provider. */
        public ?CarbonImmutable $createdAt = null,

        /** When the record was last updated at the provider. */
        public ?CarbonImmutable $updatedAt = null,

        /**
         * Raw API response payload - preserved for debugging / audit.
         *
         * @var array<string, mixed>
         */
        public array $raw = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'tracking_number' => $this->trackingNumber,
            'provider' => $this->provider->value,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_label_fr' => $this->status->labelFr(),
            'status_label_ar' => $this->status->labelAr(),
            'recipient_name' => $this->recipientName,
            'phone' => $this->phone,
            'address' => $this->address,
            'to_wilaya_id' => $this->toWilayaId,
            'to_commune' => $this->toCommune,
            'price' => $this->price,
            'shipping_fee' => $this->shippingFee,
            'raw_status' => $this->rawStatus,
            'notes' => $this->notes,
            'created_at' => $this->createdAt?->toIso8601String(),
            'updated_at' => $this->updatedAt?->toIso8601String(),
        ];
    }

    public function label(): string
    {
        return $this->status->label();
    }

    public function labelFr(): string
    {
        return $this->status->labelFr();
    }

    public function labelAr(): string
    {
        return $this->status->labelAr();
    }

    public function isDelivered(): bool
    {
        return $this->status->isSuccessful();
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }
}
