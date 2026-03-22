<?php

declare(strict_types=1);

namespace Uften\Courier\Data;

use Uften\Courier\Enums\DeliveryType;

/**
 * Unified order-creation payload.
 *
 * All adapters receive this DTO and are responsible for mapping its fields
 * to whatever shape their API expects. This means the caller never has to
 * remember which field names each provider uses.
 */
final readonly class CreateOrderData
{
    public function __construct(
        /** Your internal order/reference ID. */
        public string $orderId,

        /** Recipient's first name. */
        public string $firstName,

        /** Recipient's last name / family name. */
        public string $lastName,

        /** Primary contact phone number (Algerian format: 05/06/07XXXXXXXX). */
        public string $phone,

        /** Full delivery address string. */
        public string $address,

        /** Destination wilaya ID (1-58). */
        public int|string $toWilayaId,

        /** Destination commune / city name. */
        public string $toCommune,

        /** Description of the product(s) in the parcel. */
        public string $productDescription,

        /** Cash-on-delivery amount in DZD. 0 = free shipping / prepaid. */
        public float $price,

        /** Home delivery or stop-desk. */
        public DeliveryType $deliveryType = DeliveryType::HOME,

        /** Whether the delivery fee is waived (free shipping). */
        public bool $freeShipping = false,

        /** Whether this parcel has an exchange/return product. */
        public bool $hasExchange = false,

        /** Product to be collected in exchange (if hasExchange = true). */
        public ?string $exchangeProduct = null,

        /** Stop-desk ID when deliveryType = STOP_DESK. */
        public ?int $stopDeskId = null,

        /** Origin wilaya ID (required by some providers). */
        public int|string|null $fromWilayaId = null,

        /** Secondary / alternative contact phone number. */
        public ?string $phoneAlt = null,

        /** Additional notes or delivery instructions. */
        public ?string $notes = null,

        /** Parcel weight in kilograms. */
        public ?float $weight = null,

        /** Parcel length in centimetres. */
        public ?float $length = null,

        /** Parcel width in centimetres. */
        public ?float $width = null,

        /** Parcel height in centimetres. */
        public ?float $height = null,
    ) {}

    /**
     * Create from a plain associative array (useful when accepting raw request input).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            orderId: (string) ($data['order_id'] ?? $data['orderId'] ?? ''),
            firstName: (string) ($data['first_name'] ?? $data['firstName'] ?? ''),
            lastName: (string) ($data['last_name'] ?? $data['lastName'] ?? ''),
            phone: (string) ($data['phone'] ?? $data['contact_phone'] ?? ''),
            address: (string) ($data['address'] ?? ''),
            toWilayaId: (int) ($data['to_wilaya_id'] ?? $data['toWilayaId'] ?? 0),
            toCommune: (string) ($data['to_commune'] ?? $data['toCommune'] ?? $data['to_commune_name'] ?? ''),
            productDescription: (string) ($data['product_description'] ?? $data['product_list'] ?? ''),
            price: (float) ($data['price'] ?? 0),
            deliveryType: isset($data['delivery_type'])
                ? DeliveryType::from((int) $data['delivery_type'])
                : (($data['is_stopdesk'] ?? false) ? DeliveryType::STOP_DESK : DeliveryType::HOME),
            freeShipping: (bool) ($data['free_shipping'] ?? $data['freeshipping'] ?? false),
            hasExchange: (bool) ($data['has_exchange'] ?? $data['hasExchange'] ?? false),
            exchangeProduct: isset($data['exchange_product']) ? (string) $data['exchange_product'] : (isset($data['product_to_collect']) ? (string) $data['product_to_collect'] : null),
            stopDeskId: isset($data['stop_desk_id']) ? (int) $data['stop_desk_id'] : (isset($data['stopdesk_id']) ? (int) $data['stopdesk_id'] : null),
            fromWilayaId: isset($data['from_wilaya_id']) ? (int) $data['from_wilaya_id'] : (isset($data['fromWilayaId']) ? (int) $data['fromWilayaId'] : null),
            phoneAlt: isset($data['phone_alt']) ? (string) $data['phone_alt'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            weight: isset($data['weight']) ? (float) $data['weight'] : null,
            length: isset($data['length']) ? (float) $data['length'] : null,
            width: isset($data['width']) ? (float) $data['width'] : null,
            height: isset($data['height']) ? (float) $data['height'] : null,
        );
    }

    /**
     * Dump the DTO back to a plain array (snake_case keys).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone' => $this->phone,
            'address' => $this->address,
            'to_wilaya_id' => $this->toWilayaId,
            'to_commune' => $this->toCommune,
            'product_description' => $this->productDescription,
            'price' => $this->price,
            'delivery_type' => $this->deliveryType->value,
            'free_shipping' => $this->freeShipping,
            'has_exchange' => $this->hasExchange,
            'exchange_product' => $this->exchangeProduct,
            'stop_desk_id' => $this->stopDeskId,
            'from_wilaya_id' => $this->fromWilayaId,
            'phone_alt' => $this->phoneAlt,
            'notes' => $this->notes,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
