<?php

declare(strict_types=1);

namespace Uften\Courier\Adapters;

use GuzzleHttp\Client;
use Uften\Courier\Data\CreateOrderData;
use Uften\Courier\Data\Credentials\TokenCredentials;
use Uften\Courier\Data\LabelData;
use Uften\Courier\Data\OrderData;
use Uften\Courier\Data\RateData;
use Uften\Courier\Enums\DeliveryType;
use Uften\Courier\Enums\Provider;
use Uften\Courier\Enums\TrackingStatus;
use Uften\Courier\Exceptions\CourierException;
use Uften\Courier\Exceptions\OrderNotFoundException;

/**
 * Adapter for the Ecotrack API engine.
 *
 * Handles the generic Ecotrack provider AND all 22 branded sub-providers
 * (DHD, Conexlog, Anderson Delivery, etc.) — they share the same API
 * surface; only the base URL and metadata differ.
 *
 * Pass the correct Provider enum case to the constructor so that:
 *   - The right subdomain URL is used.
 *   - metadata() returns the correct provider branding.
 *
 * Auth: Bearer token.
 */
final class EcotrackAdapter extends AbstractAdapter
{
    private const array STATUS_MAP = [
        'created' => TrackingStatus::PENDING,
        'pending' => TrackingStatus::PENDING,
        'en attente' => TrackingStatus::PENDING,
        'ready' => TrackingStatus::PENDING,
        'picked_up' => TrackingStatus::PICKED_UP,
        'collected' => TrackingStatus::PICKED_UP,
        'ramassé' => TrackingStatus::PICKED_UP,
        'in_hub' => TrackingStatus::IN_TRANSIT,
        'in_transit' => TrackingStatus::IN_TRANSIT,
        'en transit' => TrackingStatus::IN_TRANSIT,
        'transferred' => TrackingStatus::IN_TRANSIT,
        'out_for_delivery' => TrackingStatus::OUT_FOR_DELIVERY,
        'en cours de livraison' => TrackingStatus::OUT_FOR_DELIVERY,
        'delivered' => TrackingStatus::DELIVERED,
        'livré' => TrackingStatus::DELIVERED,
        'delivery_failed' => TrackingStatus::FAILED_DELIVERY,
        'failed' => TrackingStatus::FAILED_DELIVERY,
        'tentative échouée' => TrackingStatus::FAILED_DELIVERY,
        'absent' => TrackingStatus::FAILED_DELIVERY,
        'refused' => TrackingStatus::FAILED_DELIVERY,
        'return_initiated' => TrackingStatus::RETURNING,
        'returning' => TrackingStatus::RETURNING,
        'en retour' => TrackingStatus::RETURNING,
        'returned' => TrackingStatus::RETURNED,
        'retourné' => TrackingStatus::RETURNED,
        'cancelled' => TrackingStatus::CANCELLED,
        'annulé' => TrackingStatus::CANCELLED,
        'stop_desk' => TrackingStatus::READY_FOR_PICKUP,
        'exception' => TrackingStatus::EXCEPTION,
        'lost' => TrackingStatus::EXCEPTION,
        'damaged' => TrackingStatus::EXCEPTION,
    ];

    public function __construct(
        private readonly TokenCredentials $credentials,
        /**
         * Defaults to ECOTRACK (generic base).
         * Pass any Ecotrack-engine Provider case (DHD, CONEXLOG, ANDERSON, etc.)
         * to have the adapter use that provider's subdomain and metadata.
         */
        Provider $provider = Provider::ECOTRACK,
        ?Client $httpClient = null,
    ) {
        parent::__construct(
            baseUrl: $provider->baseUrl(),
            defaultHeaders: [
                'Authorization' => "Bearer {$this->credentials->token}",
            ],
            httpClient: $httpClient,
        );

        $this->providerEnum = $provider;
    }

    public function normalizeStatus(string $rawStatus): TrackingStatus
    {
        return self::STATUS_MAP[mb_strtolower(trim($rawStatus))] ?? TrackingStatus::UNKNOWN;
    }

    public function testCredentials(): bool
    {
        try {
            $this->get('api/v1/parcels', ['per_page' => 1]);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function getRates(?int $fromWilayaId = null, ?int $toWilayaId = null): array
    {
        $params = [];
        if ($toWilayaId !== null) {
            $params['wilaya_id'] = $toWilayaId;
        }

        $data = $this->get('api/v1/tarifs', $params);
        $rows = $data['tarifs'] ?? $data['data'] ?? $data;

        if (! is_array($rows)) {
            return [];
        }

        return array_map(fn (array $item): RateData => new RateData(
            provider: $this->providerEnum,
            toWilayaId: (int) ($item['wilaya_id'] ?? 0),
            toWilayaName: (string) ($item['wilaya_name'] ?? ''),
            homeDeliveryPrice: (float) ($item['home_price'] ?? $item['price'] ?? 0),
            stopDeskPrice: (float) ($item['stopdesk_price'] ?? $item['price'] ?? 0),
            deliveryType: DeliveryType::HOME,
            fromWilayaId: $fromWilayaId,
        ), $rows);
    }

    public function getCreateOrderValidationRules(): array
    {
        return [
            'order_id' => ['required', 'string'],
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'phone' => ['required', 'string'],
            'address' => ['required', 'string'],
            'to_wilaya_id' => ['required', 'integer', 'between:1,58'],
            'to_commune' => ['required', 'string'],
            'product_description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'delivery_type' => ['required', 'integer', 'in:1,2'],
            'weight' => ['nullable', 'numeric'],
        ];
    }

    public function createOrder(CreateOrderData $data): OrderData
    {
        $payload = [
            'reference' => $data->orderId,
            'firstname' => $data->firstName,
            'lastname' => $data->lastName,
            'phone' => $data->phone,
            'address' => $data->address,
            'wilaya_id' => $data->toWilayaId,
            'commune' => $data->toCommune,
            'product' => $data->productDescription,
            'cod' => $data->price,
            'delivery_type' => $data->deliveryType->value,
            'is_fragile' => false,
        ];

        if ($data->phoneAlt !== null) {
            $payload['phone2'] = $data->phoneAlt;
        }
        if ($data->notes !== null) {
            $payload['note'] = $data->notes;
        }
        if ($data->weight !== null) {
            $payload['weight'] = $data->weight;
        }
        if ($data->stopDeskId !== null) {
            $payload['stop_desk_id'] = $data->stopDeskId;
        }
        if ($data->hasExchange && $data->exchangeProduct !== null) {
            $payload['exchange_product'] = $data->exchangeProduct;
        }

        return $this->hydrateOrder($this->post('api/v1/parcels', $payload));
    }

    public function getOrder(string $trackingNumber): OrderData
    {
        $response = $this->get("api/v1/parcels/{$trackingNumber}");

        if (empty($response) || isset($response['error'])) {
            throw new OrderNotFoundException($trackingNumber);
        }

        return $this->hydrateOrder($response);
    }

    public function getLabel(string $trackingNumber): LabelData
    {
        $response = $this->get("api/v1/parcels/{$trackingNumber}/label");

        if (isset($response['url'])) {
            return LabelData::fromUrl($this->providerEnum, $trackingNumber, $response['url']);
        }

        if (isset($response['label'])) {
            return LabelData::fromBase64($this->providerEnum, $trackingNumber, $response['label']);
        }

        throw new CourierException(
            "{$this->providerEnum->label()} returned an unrecognisable label format for [{$trackingNumber}].",
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function hydrateOrder(array $raw): OrderData
    {
        $rawStatus = (string) ($raw['status'] ?? '');

        return new OrderData(
            orderId: (string) ($raw['reference'] ?? $raw['id'] ?? ''),
            trackingNumber: (string) ($raw['tracking'] ?? $raw['barcode'] ?? $raw['id'] ?? ''),
            provider: $this->providerEnum,
            status: $this->normalizeStatus($rawStatus),
            recipientName: trim(($raw['firstname'] ?? '').' '.($raw['lastname'] ?? '')),
            phone: (string) ($raw['phone'] ?? ''),
            address: (string) ($raw['address'] ?? ''),
            toWilayaId: (int) ($raw['wilaya_id'] ?? 0),
            toCommune: (string) ($raw['commune'] ?? ''),
            price: (float) ($raw['cod'] ?? 0),
            shippingFee: isset($raw['delivery_fee']) ? (float) $raw['delivery_fee'] : null,
            rawStatus: $rawStatus,
            notes: $raw['note'] ?? null,
            createdAt: $this->parseDate($raw['created_at'] ?? null),
            updatedAt: $this->parseDate($raw['updated_at'] ?? null),
            raw: $raw,
        );
    }
}
