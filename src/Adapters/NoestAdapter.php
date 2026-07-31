<?php

declare(strict_types=1);

namespace Uften\Courier\Adapters;

use GuzzleHttp\Client;
use Uften\Courier\Data\CreateOrderData;
use Uften\Courier\Data\Credentials\TokenCredentials;
use Uften\Courier\Data\LabelData;
use Uften\Courier\Data\OrderData;
use Uften\Courier\Enums\DeliveryType;
use Uften\Courier\Enums\Provider;
use Uften\Courier\Enums\TrackingStatus;
use Uften\Courier\Exceptions\CourierException;
use Uften\Courier\Exceptions\OrderNotFoundException;

/**
 * Adapter for the Noest Express API.
 *
 * Auth : Bearer <token>
 * Base : https://app.noest-dz.com
 */
final class NoestAdapter extends AbstractAdapter
{
    private const array STATUS_MAP = [
        'pending' => TrackingStatus::PENDING,
        'en_attente' => TrackingStatus::PENDING,
        'created' => TrackingStatus::PENDING,
        'collected' => TrackingStatus::PICKED_UP,
        'picked_up' => TrackingStatus::PICKED_UP,
        'in_transit' => TrackingStatus::IN_TRANSIT,
        'en_transit' => TrackingStatus::IN_TRANSIT,
        'out_for_delivery' => TrackingStatus::OUT_FOR_DELIVERY,
        'en_livraison' => TrackingStatus::OUT_FOR_DELIVERY,
        'delivered' => TrackingStatus::DELIVERED,
        'livré' => TrackingStatus::DELIVERED,
        'failed' => TrackingStatus::FAILED_DELIVERY,
        'refused' => TrackingStatus::FAILED_DELIVERY,
        'client_absent' => TrackingStatus::FAILED_DELIVERY,
        'returning' => TrackingStatus::RETURNING,
        'returned' => TrackingStatus::RETURNED,
        'cancelled' => TrackingStatus::CANCELLED,
        'ready_for_pickup' => TrackingStatus::READY_FOR_PICKUP,
        'stop_desk' => TrackingStatus::READY_FOR_PICKUP,
    ];

    public function __construct(
        private readonly TokenCredentials $credentials,
        ?Client $httpClient = null,
    ) {
        parent::__construct(
            baseUrl: Provider::NOEST->baseUrl(),
            defaultHeaders: [
                'Authorization' => "Bearer {$this->credentials->token}",
            ],
            httpClient: $httpClient,
        );

        $this->providerEnum = Provider::NOEST;
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
        ];
    }

    public function createOrder(CreateOrderData $data): OrderData
    {
        $payload = [
            'reference' => $data->orderId,
            'name' => trim("{$data->firstName} {$data->lastName}"),
            'phone' => $data->phone,
            'address' => $data->address,
            'wilaya_id' => $data->toWilayaId,
            'commune' => $data->toCommune,
            'product' => $data->productDescription,
            'price' => $data->price,
            'weight' => $data->weight ?? 0.5,
            'delivery_type' => $data->deliveryType === DeliveryType::STOP_DESK ? 2 : 1,
        ];

        $response = $this->post('api/v1/parcels', $payload);

        return $this->hydrateOrder($response);
    }

    public function getOrder(string $trackingNumber): OrderData
    {
        try {
            $response = $this->get("api/v1/parcels/{$trackingNumber}");
        } catch (CourierException $e) {
            if ($e->getCode() === 404) {
                throw new OrderNotFoundException($trackingNumber, previous: $e);
            }
            throw $e;
        }

        return $this->hydrateOrder($response);
    }

    public function getLabel(string $trackingNumber): LabelData
    {
        $response = $this->get("api/v1/parcels/{$trackingNumber}/label");

        return LabelData::fromUrl($this->providerEnum, $trackingNumber, $response['label_url'] ?? $response['url'] ?? '');
    }

    public function cancelOrder(string $trackingNumber): bool
    {
        $this->delete("api/v1/parcels/{$trackingNumber}");

        return true;
    }

    private function hydrateOrder(array $raw): OrderData
    {
        $rawStatus = (string) ($raw['status'] ?? '');

        return new OrderData(
            orderId: (string) ($raw['reference'] ?? $raw['order_id'] ?? $raw['id'] ?? ''),
            trackingNumber: (string) ($raw['tracking'] ?? $raw['id'] ?? ''),
            provider: $this->providerEnum,
            status: $this->normalizeStatus($rawStatus),
            recipientName: (string) ($raw['name'] ?? ''),
            phone: (string) ($raw['phone'] ?? ''),
            address: (string) ($raw['address'] ?? ''),
            toWilayaId: (int) ($raw['wilaya_id'] ?? 0),
            toCommune: (string) ($raw['commune'] ?? ''),
            price: (float) ($raw['price'] ?? 0),
            shippingFee: isset($raw['shipping_fee']) ? (float) $raw['shipping_fee'] : null,
            rawStatus: $rawStatus,
            raw: $raw,
        );
    }
}
