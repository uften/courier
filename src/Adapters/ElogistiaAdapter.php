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
 * Adapter for the Elogistia API.
 *
 * Auth : Bearer <token>
 * Base : https://api.elogistia.com
 */
final class ElogistiaAdapter extends AbstractAdapter
{
    private const array STATUS_MAP = [
        'pending' => TrackingStatus::PENDING,
        'en attente' => TrackingStatus::PENDING,
        'picked_up' => TrackingStatus::PICKED_UP,
        'collecté' => TrackingStatus::PICKED_UP,
        'in_transit' => TrackingStatus::IN_TRANSIT,
        'en transit' => TrackingStatus::IN_TRANSIT,
        'out_for_delivery' => TrackingStatus::OUT_FOR_DELIVERY,
        'en livraison' => TrackingStatus::OUT_FOR_DELIVERY,
        'delivered' => TrackingStatus::DELIVERED,
        'livré' => TrackingStatus::DELIVERED,
        'failed' => TrackingStatus::FAILED_DELIVERY,
        'échec' => TrackingStatus::FAILED_DELIVERY,
        'returned' => TrackingStatus::RETURNED,
        'retourné' => TrackingStatus::RETURNED,
        'cancelled' => TrackingStatus::CANCELLED,
        'annulé' => TrackingStatus::CANCELLED,
    ];

    public function __construct(
        private readonly TokenCredentials $credentials,
        ?Client $httpClient = null,
    ) {
        parent::__construct(
            baseUrl: Provider::ELOGISTIA->baseUrl(),
            defaultHeaders: [
                'Authorization' => "Bearer {$this->credentials->token}",
            ],
            httpClient: $httpClient,
        );

        $this->providerEnum = Provider::ELOGISTIA;
    }

    public function normalizeStatus(string $rawStatus): TrackingStatus
    {
        return self::STATUS_MAP[mb_strtolower(trim($rawStatus))] ?? TrackingStatus::UNKNOWN;
    }

    public function testCredentials(): bool
    {
        try {
            $this->get('v1/orders', ['per_page' => 1]);

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
            'recipient_name' => trim("{$data->firstName} {$data->lastName}"),
            'recipient_phone' => $data->phone,
            'recipient_address' => $data->address,
            'wilaya_id' => $data->toWilayaId,
            'commune' => $data->toCommune,
            'product' => $data->productDescription,
            'amount' => $data->price,
            'weight' => $data->weight ?? 0.5,
            'delivery_type' => $data->deliveryType === DeliveryType::STOP_DESK ? 'stop_desk' : 'home',
        ];

        $response = $this->post('v1/orders', $payload);

        return $this->hydrateOrder($response);
    }

    public function getOrder(string $trackingNumber): OrderData
    {
        try {
            $response = $this->get("v1/orders/{$trackingNumber}");
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
        $response = $this->get("v1/orders/{$trackingNumber}/label");

        if (isset($response['label_url'])) {
            return LabelData::fromUrl($this->providerEnum, $trackingNumber, $response['label_url']);
        }

        return LabelData::fromUrl($this->providerEnum, $trackingNumber, $response['url'] ?? '');
    }

    public function cancelOrder(string $trackingNumber): bool
    {
        $this->post("v1/orders/{$trackingNumber}/cancel");

        return true;
    }

    private function hydrateOrder(array $raw): OrderData
    {
        $rawStatus = (string) ($raw['status'] ?? '');

        return new OrderData(
            orderId: (string) ($raw['reference'] ?? $raw['order_id'] ?? $raw['id'] ?? ''),
            trackingNumber: (string) ($raw['tracking_code'] ?? $raw['tracking_number'] ?? $raw['id'] ?? ''),
            provider: $this->providerEnum,
            status: $this->normalizeStatus($rawStatus),
            recipientName: (string) ($raw['recipient_name'] ?? ''),
            phone: (string) ($raw['recipient_phone'] ?? ''),
            address: (string) ($raw['recipient_address'] ?? ''),
            toWilayaId: (int) ($raw['wilaya_id'] ?? 0),
            toCommune: (string) ($raw['commune'] ?? ''),
            price: (float) ($raw['amount'] ?? $raw['price'] ?? 0),
            shippingFee: isset($raw['shipping_fee']) ? (float) $raw['shipping_fee'] : null,
            rawStatus: $rawStatus,
            raw: $raw,
        );
    }
}
