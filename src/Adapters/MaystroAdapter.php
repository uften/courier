<?php

declare(strict_types=1);

namespace Uften\Courier\Adapters;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
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
 * Adapter for the Maystro Delivery API.
 *
 * Auth : Token <token>  (Django REST Framework token, NOT Bearer).
 * Base : https://backend.maystro-delivery.com/api/
 *
 * Delivery type mapping (Maystro-specific):
 *   0 = home delivery  (our DeliveryType::HOME)
 *   1 = stop desk      (our DeliveryType::STOP_DESK)
 */
final class MaystroAdapter extends AbstractAdapter
{
    private const array STATUS_MAP = [
        'initial' => TrackingStatus::PENDING,
        'pending' => TrackingStatus::PENDING,
        'waiting_for_pickup' => TrackingStatus::PENDING,
        'picked_up' => TrackingStatus::PICKED_UP,
        'ready_to_ship' => TrackingStatus::PICKED_UP,
        'in_hub' => TrackingStatus::IN_TRANSIT,
        'in_transit' => TrackingStatus::IN_TRANSIT,
        'transferred' => TrackingStatus::IN_TRANSIT,
        'out_for_delivery' => TrackingStatus::OUT_FOR_DELIVERY,
        'delivery_in_progress' => TrackingStatus::OUT_FOR_DELIVERY,
        'delivered' => TrackingStatus::DELIVERED,
        'delivery_failed' => TrackingStatus::FAILED_DELIVERY,
        'failed_delivery' => TrackingStatus::FAILED_DELIVERY,
        'refused' => TrackingStatus::FAILED_DELIVERY,
        'client_absent' => TrackingStatus::FAILED_DELIVERY,
        'return_in_progress' => TrackingStatus::RETURNING,
        'returning' => TrackingStatus::RETURNING,
        'returned' => TrackingStatus::RETURNED,
        'return_received' => TrackingStatus::RETURNED,
        'cancelled' => TrackingStatus::CANCELLED,
        'stop_desk' => TrackingStatus::READY_FOR_PICKUP,
        'ready_for_pickup' => TrackingStatus::READY_FOR_PICKUP,
        'lost' => TrackingStatus::EXCEPTION,
        'damaged' => TrackingStatus::EXCEPTION,
        'problem' => TrackingStatus::EXCEPTION,
    ];

    public function __construct(
        private readonly TokenCredentials $credentials,
        ?Client $httpClient = null,
    ) {
        parent::__construct(
            baseUrl: Provider::MAYSTRO->baseUrl(),
            defaultHeaders: [
                // Maystro uses Django REST Framework Token auth — NOT Bearer
                'Authorization' => "Token {$this->credentials->token}",
            ],
            httpClient: $httpClient,
        );

        $this->providerEnum = Provider::MAYSTRO;
    }

    public function normalizeStatus(string $rawStatus): TrackingStatus
    {
        return self::STATUS_MAP[mb_strtolower($rawStatus)] ?? TrackingStatus::UNKNOWN;
    }

    public function testCredentials(): bool
    {
        try {
            // Maystro: fetch wilayas to confirm the token is valid
            $this->get('base/wilayas/', ['country' => 1]);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function getCreateOrderValidationRules(): array
    {
        return [
            'order_id' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'numeric', 'digits_between:9,10'],
            'address' => ['nullable', 'string', 'max:255'],
            'to_wilaya_id' => ['required', 'integer', 'min:1', 'max:58'],
            'to_commune' => ['required', 'integer', 'min:1'],
            'product_description' => ['required', 'array'],    // Maystro expects a products array
            'price' => ['required', 'integer'],
            'delivery_type' => ['required', 'integer', 'in:1,2'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function createOrder(CreateOrderData $data): OrderData
    {
        // Maystro delivery_type: 0 = home, 1 = stop desk (inverse of our enum values)
        $maystroDeliveryType = $data->deliveryType === DeliveryType::STOP_DESK ? 1 : 0;

        $payload = [
            'wilaya' => $data->toWilayaId,
            'commune' => $data->toCommune,
            'customer_phone' => $data->phone,
            'customer_name' => $data->firstName.' '.$data->lastName,
            'product_price' => (int) $data->price,
            'delivery_type' => $maystroDeliveryType,
            'source' => 4,                          // required constant per Maystro docs
            'products' => [['name' => $data->productDescription, 'quantity' => 1]],
            'external_order_id' => $data->orderId,
        ];

        if ($data->notes !== null) {
            $payload['note_to_driver'] = $data->notes;
        }

        if ($data->address !== null) {
            $payload['destination_text'] = $data->address;
        }

        if ($data->stopDeskId !== null) {
            $payload['stop_desk_id'] = $data->stopDeskId;
        }

        $response = $this->post('stores/orders/', $payload);

        return $this->hydrateOrder($response);
    }

    public function getOrder(string $trackingNumber): OrderData
    {
        $response = $this->get("stores/orders/{$trackingNumber}/");

        if (empty($response)) {
            throw new OrderNotFoundException($trackingNumber);
        }

        return $this->hydrateOrder($response);
    }

    public function getLabel(string $trackingNumber): LabelData
    {
        // Maystro returns raw PDF bytes from a POST endpoint
        $rawPdf = $this->requestRaw('POST', 'delivery/starter/starter_bordureau/', [
            RequestOptions::JSON => [
                'all_created' => true,
                'orders_ids' => [$trackingNumber],
            ],
        ]);

        if ($rawPdf === '') {
            throw new CourierException(
                "Maystro returned an empty label for [{$trackingNumber}].",
            );
        }

        return LabelData::fromBase64(
            Provider::MAYSTRO,
            $trackingNumber,
            base64_encode($rawPdf),
        );
    }

    /**
     * Create a product in the Maystro store catalogue.
     *
     * This is a Maystro-specific operation — it is not part of the
     * ProviderAdapter contract. Type-hint MaystroAdapter directly if you need it:
     *
     * ```php
     *
     * /** @var MaystroAdapter {@*}
     * $maystro = Courier::provider(Provider::MAYSTRO);
     * $maystro->createProduct($storeId, 'Samsung Galaxy S24', null);
     * ```
     *
     * @return array<string, mixed>
     */
    public function createProduct(
        string $storeId,
        string $logisticalDescription,
        ?string $productId = null,
    ): array {
        $payload = [
            'store_id' => $storeId,
            'logistical_description' => $logisticalDescription,
        ];

        if ($productId !== null && $productId !== '') {
            $payload['product_id'] = $productId;
        }

        return $this->post('stores/product/', $payload);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function hydrateOrder(array $raw): OrderData
    {
        $rawStatus = (string) ($raw['status'] ?? '');
        $name = (string) ($raw['customer_name'] ?? '');

        return new OrderData(
            orderId: (string) ($raw['external_order_id'] ?? $raw['id'] ?? ''),
            trackingNumber: (string) ($raw['tracking'] ?? (string) ($raw['id'] ?? '')),
            provider: Provider::MAYSTRO,
            status: $this->normalizeStatus($rawStatus),
            recipientName: $name,
            phone: (string) ($raw['customer_phone'] ?? ''),
            address: (string) ($raw['destination_text'] ?? $raw['address'] ?? ''),
            toWilayaId: (int) ($raw['wilaya'] ?? 0),
            toCommune: (string) ($raw['commune'] ?? ''),
            price: (float) ($raw['product_price'] ?? 0),
            shippingFee: isset($raw['delivery_fee']) ? (float) $raw['delivery_fee'] : null,
            rawStatus: $rawStatus,
            notes: $raw['note_to_driver'] ?? null,
            createdAt: $this->parseDate($raw['created_at'] ?? null),
            updatedAt: $this->parseDate($raw['updated_at'] ?? null),
            raw: $raw,
        );
    }
}
