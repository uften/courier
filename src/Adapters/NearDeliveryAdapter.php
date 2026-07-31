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
 * Adapter for the Near Delivery API.
 *
 * Auth : Bearer <token>
 * Base : https://api.neardelivery.app/api/v1
 */
final class NearDeliveryAdapter extends AbstractAdapter
{
    private const array STATUS_MAP = [
        'pending' => TrackingStatus::PENDING,
        'confirmed' => TrackingStatus::PENDING,
        'picked_up' => TrackingStatus::PICKED_UP,
        'in_transit' => TrackingStatus::IN_TRANSIT,
        'out_for_delivery' => TrackingStatus::OUT_FOR_DELIVERY,
        'delivered' => TrackingStatus::DELIVERED,
        'failed' => TrackingStatus::FAILED_DELIVERY,
        'refused' => TrackingStatus::FAILED_DELIVERY,
        'returning' => TrackingStatus::RETURNING,
        'returned' => TrackingStatus::RETURNED,
        'cancelled' => TrackingStatus::CANCELLED,
        'stop_desk' => TrackingStatus::READY_FOR_PICKUP,
    ];

    public function __construct(
        private readonly TokenCredentials $credentials,
        ?Client $httpClient = null,
    ) {
        parent::__construct(
            baseUrl: Provider::NEAR_DELIVERY->baseUrl(),
            defaultHeaders: [
                'Authorization' => "Bearer {$this->credentials->token}",
            ],
            httpClient: $httpClient,
        );

        $this->providerEnum = Provider::NEAR_DELIVERY;
    }

    public function normalizeStatus(string $rawStatus): TrackingStatus
    {
        return self::STATUS_MAP[mb_strtolower(trim($rawStatus))] ?? TrackingStatus::UNKNOWN;
    }

    public function testCredentials(): bool
    {
        try {
            $this->get('shipments', ['per_page' => 1]);

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
            'cod' => $data->price,
            'weight' => $data->weight ?? 0.5,
            'is_stop_desk' => $data->deliveryType === DeliveryType::STOP_DESK,
        ];

        $response = $this->post('shipments', $payload);

        return $this->hydrateOrder($response);
    }

    public function getOrder(string $trackingNumber): OrderData
    {
        try {
            $response = $this->get("shipments/{$trackingNumber}");
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
        $response = $this->get("shipments/{$trackingNumber}/label");

        return LabelData::fromUrl($this->providerEnum, $trackingNumber, $response['label_url'] ?? $response['url'] ?? '');
    }

    public function cancelOrder(string $trackingNumber): bool
    {
        $this->delete("shipments/{$trackingNumber}");

        return true;
    }

    private function hydrateOrder(array $raw): OrderData
    {
        $rawStatus = (string) ($raw['status'] ?? '');

        return new OrderData(
            orderId: (string) ($raw['reference'] ?? $raw['order_id'] ?? $raw['id'] ?? ''),
            trackingNumber: (string) ($raw['tracking_number'] ?? $raw['id'] ?? ''),
            provider: $this->providerEnum,
            status: $this->normalizeStatus($rawStatus),
            recipientName: (string) ($raw['name'] ?? ''),
            phone: (string) ($raw['phone'] ?? ''),
            address: (string) ($raw['address'] ?? ''),
            toWilayaId: (int) ($raw['wilaya_id'] ?? 0),
            toCommune: (string) ($raw['commune'] ?? ''),
            price: (float) ($raw['cod'] ?? $raw['price'] ?? 0),
            shippingFee: isset($raw['shipping_fee']) ? (float) $raw['shipping_fee'] : null,
            rawStatus: $rawStatus,
            raw: $raw,
        );
    }
}
