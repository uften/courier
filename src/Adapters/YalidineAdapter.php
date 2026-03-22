<?php

declare(strict_types=1);

namespace Uften\Courier\Adapters;

use GuzzleHttp\Client;
use Uften\Courier\Data\CreateOrderData;
use Uften\Courier\Data\Credentials\YalidineCredentials;
use Uften\Courier\Data\LabelData;
use Uften\Courier\Data\OrderData;
use Uften\Courier\Data\RateData;
use Uften\Courier\Enums\DeliveryType;
use Uften\Courier\Enums\Provider;
use Uften\Courier\Enums\TrackingStatus;
use Uften\Courier\Exceptions\CourierException;
use Uften\Courier\Exceptions\OrderNotFoundException;

/**
 * Adapter for the Yalidine API engine.
 *
 * Also used for Yalitec (same engine, different subdomain).
 * Auth: X-API-ID + X-API-TOKEN headers.
 */
final class YalidineAdapter extends AbstractAdapter
{
    private const array STATUS_MAP = [
        'En préparation' => TrackingStatus::PENDING,
        'Prêt à expédier' => TrackingStatus::PENDING,
        'En attente' => TrackingStatus::PENDING,
        'Enlevé' => TrackingStatus::PICKED_UP,
        'Reçu à l\'agence' => TrackingStatus::IN_TRANSIT,
        'Transféré' => TrackingStatus::IN_TRANSIT,
        'En cours de livraison' => TrackingStatus::OUT_FOR_DELIVERY,
        'En attente du client' => TrackingStatus::OUT_FOR_DELIVERY,
        'Livré' => TrackingStatus::DELIVERED,
        'Tentative échouée' => TrackingStatus::FAILED_DELIVERY,
        'Absent' => TrackingStatus::FAILED_DELIVERY,
        'Reporté' => TrackingStatus::FAILED_DELIVERY,
        'Refusé' => TrackingStatus::FAILED_DELIVERY,
        'En retour' => TrackingStatus::RETURNING,
        'Retourné' => TrackingStatus::RETURNED,
        'Annulé' => TrackingStatus::CANCELLED,
        'Stop desk' => TrackingStatus::READY_FOR_PICKUP,
        'Disponible en agence' => TrackingStatus::READY_FOR_PICKUP,
        'Perdu' => TrackingStatus::EXCEPTION,
        'Endommagé' => TrackingStatus::EXCEPTION,
    ];

    public function __construct(
        private readonly YalidineCredentials $credentials,
        /**
         * Defaults to YALIDINE; pass Provider::YALITEC to use the Yalitec subdomain
         * with the same API engine.
         */
        Provider $provider = Provider::YALIDINE,
        ?Client $httpClient = null,
    ) {
        parent::__construct(
            baseUrl: $provider->baseUrl(),
            defaultHeaders: [
                'X-API-ID' => $this->credentials->token,
                'X-API-TOKEN' => $this->credentials->apiKey,
            ],
            httpClient: $httpClient,
        );

        $this->providerEnum = $provider;
    }

    public function normalizeStatus(string $rawStatus): TrackingStatus
    {
        return self::STATUS_MAP[$rawStatus] ?? TrackingStatus::UNKNOWN;
    }

    public function testCredentials(): bool
    {
        try {
            $response = $this->get('v1/parcels', ['page_size' => 1]);

            return isset($response['data']) || isset($response['total']);
        } catch (\Throwable) {
            return false;
        }
    }

    public function getRates(?int $fromWilayaId = null, ?int $toWilayaId = null): array
    {
        if ($fromWilayaId === null) {
            throw new CourierException(
                "{$this->providerEnum->label()} requires a \$fromWilayaId to retrieve rates.",
            );
        }

        $data = $this->get("v1/delivery-fees/{$fromWilayaId}");

        return array_map(function (array $item) use ($fromWilayaId): RateData {
            return new RateData(
                provider: $this->providerEnum,
                toWilayaId: (int) ($item['wilaya_id'] ?? 0),
                toWilayaName: (string) ($item['wilaya_name'] ?? ''),
                homeDeliveryPrice: (float) ($item['home_price'] ?? 0),
                stopDeskPrice: (float) ($item['desk_price'] ?? 0),
                deliveryType: DeliveryType::HOME,
                fromWilayaId: $fromWilayaId,
            );
        }, $data['data'] ?? $data);
    }

    public function getCreateOrderValidationRules(): array
    {
        return [
            'order_id' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^0[5-7][0-9]{8}$/'],
            'address' => ['required', 'string', 'max:500'],
            'to_wilaya_id' => ['required', 'integer', 'min:1', 'max:58'],
            'to_commune' => ['required', 'string'],
            'product_description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'delivery_type' => ['required', 'integer', 'in:1,2'],
            'from_wilaya_id' => ['nullable', 'integer', 'min:1', 'max:58'],
            'phone_alt' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'stop_desk_id' => ['nullable', 'integer'],
        ];
    }

    public function createOrder(CreateOrderData $data): OrderData
    {
        $payload = [
            'tracking' => $data->orderId,
            'firstname' => $data->firstName,
            'familyname' => $data->lastName,
            'contact_phone' => $data->phone,
            'address' => $data->address,
            'to_wilaya_id' => $data->toWilayaId,
            'to_commune_name' => $data->toCommune,
            'product_list' => $data->productDescription,
            'price' => (int) $data->price,
            'is_stopdesk' => $data->deliveryType === DeliveryType::STOP_DESK ? 1 : 0,
            'freeshipping' => $data->freeShipping ? 1 : 0,
            'has_exchange' => $data->hasExchange ? 1 : 0,
        ];

        if ($data->fromWilayaId !== null) {
            $payload['from_wilaya_id'] = $data->fromWilayaId;
        }
        if ($data->phoneAlt !== null) {
            $payload['contact_phone_b'] = $data->phoneAlt;
        }
        if ($data->notes !== null) {
            $payload['note'] = $data->notes;
        }
        if ($data->stopDeskId !== null) {
            $payload['stopdesk_id'] = $data->stopDeskId;
        }
        if ($data->weight !== null) {
            $payload['weight'] = $data->weight;
        }
        if ($data->hasExchange && $data->exchangeProduct !== null) {
            $payload['product_to_collect'] = $data->exchangeProduct;
        }

        return $this->hydrateOrder($this->post('v1/parcels', $payload));
    }

    public function getOrder(string $trackingNumber): OrderData
    {
        $response = $this->get("v1/parcels/{$trackingNumber}");

        if (empty($response)) {
            throw new OrderNotFoundException($trackingNumber);
        }

        return $this->hydrateOrder($response);
    }

    public function getLabel(string $trackingNumber): LabelData
    {
        $response = $this->get("v1/parcels/{$trackingNumber}/label");

        if (isset($response['url'])) {
            return LabelData::fromUrl($this->providerEnum, $trackingNumber, $response['url']);
        }

        if (isset($response['data'])) {
            return LabelData::fromBase64($this->providerEnum, $trackingNumber, $response['data']);
        }

        throw new CourierException(
            "{$this->providerEnum->label()} returned an unrecognisable label format for [{$trackingNumber}].",
        );
    }

    private function hydrateOrder(array $raw): OrderData
    {
        $rawStatus = (string) ($raw['last_status'] ?? $raw['status'] ?? '');

        return new OrderData(
            orderId: (string) ($raw['tracking'] ?? ''),
            trackingNumber: (string) ($raw['tracking'] ?? ''),
            provider: $this->providerEnum,
            status: $this->normalizeStatus($rawStatus),
            recipientName: trim(($raw['firstname'] ?? '').' '.($raw['familyname'] ?? '')),
            phone: (string) ($raw['contact_phone'] ?? ''),
            address: (string) ($raw['address'] ?? ''),
            toWilayaId: (int) ($raw['to_wilaya_id'] ?? 0),
            toCommune: (string) ($raw['to_commune_name'] ?? ''),
            price: (float) ($raw['price'] ?? 0),
            shippingFee: isset($raw['price_delivery']) ? (float) $raw['price_delivery'] : null,
            rawStatus: $rawStatus,
            notes: $raw['note'] ?? null,
            createdAt: $this->parseDate($raw['date'] ?? null),
            updatedAt: $this->parseDate($raw['last_update'] ?? null),
            raw: $raw,
        );
    }
}
