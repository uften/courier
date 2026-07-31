<?php

declare(strict_types=1);

namespace Uften\Courier\Adapters;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Uften\Courier\Data\CreateOrderData;
use Uften\Courier\Data\Credentials\ZrExpressNewCredentials;
use Uften\Courier\Data\LabelData;
use Uften\Courier\Data\OrderData;
use Uften\Courier\Data\RateData;
use Uften\Courier\Enums\DeliveryType;
use Uften\Courier\Enums\LabelType;
use Uften\Courier\Enums\Provider;
use Uften\Courier\Enums\TrackingStatus;
use Uften\Courier\Exceptions\CourierException;
use Uften\Courier\Exceptions\OrderNotFoundException;

/**
 * Adapter for the ZR Express NEW platform (api.zrexpress.app, v1).
 *
 * This is a fully redesigned API — it shares nothing with the legacy
 * Procolis-based ZR Express adapter.
 *
 * Auth    : X-Tenant: {tenantId}  +  X-Api-Key: {apiKey}  (two separate headers)
 * Base    : https://api.zrexpress.app
 * Docs    : https://docs.zrexpress.app/reference/createparcelendpoint
 *
 * The adapter dynamically resolves territory UUIDs via the API.
 * The district (commune) UUID can be supplied via notes:
 *
 *   "zr_district:{districtUUID}|Optional real note"
 *
 * If you also need to override the city UUID, you can provide:
 *
 *   "zr_city:{cityUUID}|zr_district:{districtUUID}|Optional real note"
 *
 * The adapter parses both formats and strips the prefixes before sending.
 *
 * -------------------------------------------------------------------------
 * createOrder() two-step flow
 * -------------------------------------------------------------------------
 * POST /api/v1/parcels returns only {"id": "uuid"}.
 * The adapter immediately calls GET /api/v1/parcels/{id} to hydrate the
 * full OrderData. Two HTTP calls, same rich response as every other adapter.
 *
 * -------------------------------------------------------------------------
 * getOrder() dual-path
 * -------------------------------------------------------------------------
 *   UUID string  → GET /api/v1/parcels/{uuid}
 *   Tracking no. → GET /api/v1/parcels/{trackingNumber}  (e.g. "16-JUKYSI-ZR")
 *
 * -------------------------------------------------------------------------
 * getLabel()
 * -------------------------------------------------------------------------
 * POST /api/v1/parcels/labels/individual with {"trackingNumbers": [tn]}.
 * Returns a time-limited Azure Blob SAS URL pointing to an HTML file
 * (4 identical A6 labels on an A4 page). Returns LabelType::HTML_URL.
 *
 * Note: the label endpoint may require an Authorization: Bearer token
 * (JWT from the login endpoint) in addition to, or instead of, X-Api-Key.
 * This adapter sends both X-Api-Key AND Authorization: Bearer {apiKey}
 * for maximum compatibility. If your account requires a separate JWT,
 * handle the login flow outside the adapter and use a custom driver.
 *
 * -------------------------------------------------------------------------
 * getRates()
 * -------------------------------------------------------------------------
 * GET /api/v1/delivery-pricing/rates — returns effective prices for all
 * destination territories. Wilaya-level entries are mapped to RateData.
 * The $fromWilayaId / $toWilayaId parameters are ignored — the endpoint
 * always returns the full rate table for the supplier's account.
 */
final class ZrExpressNewAdapter extends AbstractAdapter
{
    // -------------------------------------------------------------------------
    // Status mapping
    // -------------------------------------------------------------------------

    /**
     * @var array<string, TrackingStatus>
     */
    private const array STATUS_MAP = [
        // ── Pending group ────────────────────────────────────────────────────
        'commande_recue' => TrackingStatus::PENDING,
        'orderreceived' => TrackingStatus::PENDING,
        'en_traitement' => TrackingStatus::PENDING,
        'inprocessing' => TrackingStatus::PENDING,
        'appel_confirmation' => TrackingStatus::PENDING,
        'confirmationcall' => TrackingStatus::PENDING,
        'commande_confirmee' => TrackingStatus::PENDING,
        'orderconfirmed' => TrackingStatus::PENDING,
        'en_preparation' => TrackingStatus::PENDING,
        'inpreparation' => TrackingStatus::PENDING,

        // ── Picked up ────────────────────────────────────────────────────────
        'pret_a_expedier' => TrackingStatus::PICKED_UP,
        'readytodispatch' => TrackingStatus::PICKED_UP,

        // ── In transit ───────────────────────────────────────────────────────
        'confirme_au_bureau' => TrackingStatus::IN_TRANSIT,
        'confirmedatbranch' => TrackingStatus::IN_TRANSIT,
        'dispatch' => TrackingStatus::IN_TRANSIT,
        'dispatched' => TrackingStatus::IN_TRANSIT,
        'vers_wilaya' => TrackingStatus::IN_TRANSIT,
        'interwilayatransit' => TrackingStatus::IN_TRANSIT,
        'en_livraison' => TrackingStatus::IN_TRANSIT,
        'indelivery' => TrackingStatus::IN_TRANSIT,

        // ── Out for delivery ─────────────────────────────────────────────────
        'sortie_en_livraison' => TrackingStatus::OUT_FOR_DELIVERY,
        'outfordelivery' => TrackingStatus::OUT_FOR_DELIVERY,

        // ── Delivered ────────────────────────────────────────────────────────
        'livre' => TrackingStatus::DELIVERED,
        'delivered' => TrackingStatus::DELIVERED,
        'encaisse' => TrackingStatus::DELIVERED,
        'collected' => TrackingStatus::DELIVERED,
        'recouvert' => TrackingStatus::DELIVERED,

        // ── Failed delivery ──────────────────────────────────────────────────
        'echec_livraison' => TrackingStatus::FAILED_DELIVERY,
        'faileddelivery' => TrackingStatus::FAILED_DELIVERY,
        'delivery_failed' => TrackingStatus::FAILED_DELIVERY,
        'commande_annulee' => TrackingStatus::FAILED_DELIVERY,
        'orderrefused' => TrackingStatus::FAILED_DELIVERY,

        // ── Returning ────────────────────────────────────────────────────────
        'retour' => TrackingStatus::RETURNING,
        'returning' => TrackingStatus::RETURNING,
        'en_retour' => TrackingStatus::RETURNING,
        'inreturn' => TrackingStatus::RETURNING,

        // ── Returned ─────────────────────────────────────────────────────────
        'retourne' => TrackingStatus::RETURNED,
        'returned' => TrackingStatus::RETURNED,
        'retour_confirme' => TrackingStatus::RETURNED,
        'returnconfirmed' => TrackingStatus::RETURNED,
        'reinjecte_stock' => TrackingStatus::RETURNED,

        // ── Cancelled ────────────────────────────────────────────────────────
        'annule' => TrackingStatus::CANCELLED,
        'cancelled' => TrackingStatus::CANCELLED,

        // ── Ready for pickup (stop desk) ─────────────────────────────────────
        'disponible_bureau' => TrackingStatus::READY_FOR_PICKUP,
        'readyforpickup' => TrackingStatus::READY_FOR_PICKUP,
        'en_attente_client' => TrackingStatus::READY_FOR_PICKUP,
        'waitingclient' => TrackingStatus::READY_FOR_PICKUP,

        // ── Exception ────────────────────────────────────────────────────────
        'en_attente_echange' => TrackingStatus::EXCEPTION,
        'remboursement' => TrackingStatus::EXCEPTION,
    ];

    // Territory maps removed as per refactoring requirements.
    // Resolution is now handled dynamically via API in the service layer.

    public function __construct(
        private readonly ZrExpressNewCredentials $credentials,
        ?Client $httpClient = null,
    ) {
        parent::__construct(
            baseUrl: Provider::ZREXPRESS_NEW->baseUrl(),
            defaultHeaders: [
                'X-Tenant' => $this->credentials->tenantId,
                'X-Api-Key' => $this->credentials->apiKey,
            ],
            httpClient: $httpClient,
        );

        $this->providerEnum = Provider::ZREXPRESS_NEW;
    }

    // -------------------------------------------------------------------------
    // StatusNormalizer
    // -------------------------------------------------------------------------

    public function normalizeStatus(string $rawStatus): TrackingStatus
    {
        $key = mb_strtolower(trim($rawStatus));
        if (isset(self::STATUS_MAP[$key])) {
            return self::STATUS_MAP[$key];
        }

        // Try without underscores to handle PascalCase API responses
        $keyNoUnderscore = str_replace('_', '', $key);

        return self::STATUS_MAP[$keyNoUnderscore] ?? TrackingStatus::UNKNOWN;
    }

    // -------------------------------------------------------------------------
    // ProviderAdapter implementation
    // -------------------------------------------------------------------------

    public function testCredentials(): bool
    {
        try {
            $this->post('api/v1/workflows/search', [
                'pageNumber' => 1,
                'pageSize' => 1,
            ]);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get effective delivery rates for all wilaya territories.
     *
     * Calls GET /api/v1/delivery-pricing/rates which returns rates for every
     * destination territory. The $fromWilayaId / $toWilayaId filter parameters
     * are accepted for API consistency but ignored — the endpoint always
     * returns the full rate table for the supplier account.
     *
     * Only wilaya-level entries are returned as RateData. Commune-level
    /**
     * Get effective delivery rates from endpoint for all destination territories.
     *
     * Calls GET /api/v1/delivery-pricing/rates which returns rate objects for every destination territory.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRates(?int $fromWilayaId = null, ?int $toWilayaId = null): array
    {
        $response = $this->get('api/v1/delivery-pricing/rates');

        return $response['rates'] ?? [];
    }

    /**
     * Register a webhook endpoint for parcel status updates with ZR Express New.
     *
     * @return array<string, mixed>|null
     */
    public function registerWebhook(string $webhookUrl): ?array
    {
        $payload = [
            'url' => $webhookUrl,
            'description' => 'Rassmi Platform Order Updates',
            'eventTypes' => ['parcel.state.updated'],
        ];

        try {
            return $this->post('api/v1/suppliers/webhooks/endpoints', $payload);
        } catch (\Throwable) {
            try {
                return $this->post('api/v1/webhooks/endpoints', $payload);
            } catch (\Throwable $e) {
                if (function_exists('info')) {
                    Log::warning('ZR Express New registerWebhook failed: '.$e->getMessage());
                }

                return null;
            }
        }
    }

    /**
     * Delete a registered webhook endpoint in ZR Express New.
     */
    public function deleteWebhook(string $webhookId): bool
    {
        try {
            $this->delete('api/v1/suppliers/webhooks/endpoints/'.$webhookId);

            return true;
        } catch (\Throwable) {
            try {
                $this->delete('api/v1/webhooks/endpoints/'.$webhookId);

                return true;
            } catch (\Throwable $e) {
                if (function_exists('info')) {
                    Log::warning("ZR Express New deleteWebhook [{$webhookId}] failed: ".$e->getMessage());
                }

                return false;
            }
        }
    }

    public function getCreateOrderValidationRules(): array
    {
        return [
            'order_id' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string'],
            'address' => ['nullable', 'string', 'max:500'],
            'to_wilaya_id' => ['nullable', 'integer', 'between:1,58'],
            'to_commune' => ['nullable', 'string'],
            'product_description' => ['required', 'string', 'min:2', 'max:250'],
            'price' => ['required', 'numeric', 'min:0', 'max:150000'],
            'delivery_type' => ['required', 'integer', 'in:1,2'],
            'phone_alt' => ['nullable', 'string'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'length' => ['nullable', 'numeric', 'min:0'],
            'width' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            /**
             * Provide the district territory UUID in notes.
             * The city UUID is auto-resolved from toWilayaId via WILAYA_UUID_MAP.
             * To override the city UUID or provide both explicitly:
             *   "zr_city:{uuid}|zr_district:{uuid}|optional note"
             *   "zr_district:{uuid}|optional note"   ← preferred when toWilayaId is set
             */
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Create a new parcel.
     *
     * The city UUID is resolved automatically from CreateOrderData::$toWilayaId
     * when it maps to a known wilaya. The district (commune) UUID must be
     * provided in notes as "zr_district:{uuid}".
     *
     * @throws CourierException if the district UUID is missing from notes
     *                          and cannot be resolved automatically.
     */
    public function createOrder(CreateOrderData $data): OrderData
    {
        [$cityTerritoryId, $districtTerritoryId, $cleanNote] = $this->parseTerritoryIds(
            $data->notes,
            $data->toWilayaId,
        );

        if ($districtTerritoryId === null) {
            throw new CourierException(
                'ZR Express NEW requires a district territory UUID. '
                    .'Pass it via CreateOrderData::$notes: "zr_district:{uuid}|..." '
                    .'or ensure it is resolved in the service layer.',
            );
        }

        if ($cityTerritoryId === null) {
            throw new CourierException(
                'ZR Express NEW requires a city territory UUID. '
                    .'Ensure it is resolved via API or provided explicitly in notes.',
            );
        }

        $payload = [
            'customer' => [
                'customerId' => $this->randomUuid(),
                'name' => trim($data->firstName.' '.$data->lastName),
                'phone' => [
                    'number1' => $data->phone,
                    'number2' => $data->phoneAlt,
                ],
            ],
            'deliveryAddress' => [
                'cityTerritoryId' => $cityTerritoryId,
                'districtTerritoryId' => $districtTerritoryId,
                'street' => $data->address ?: null,
            ],
            'orderedProducts' => [
                [
                    'productName' => $data->productDescription,
                    'unitPrice' => $data->price,
                    'quantity' => 1,
                    'stockType' => 'none',
                ],
            ],
            'deliveryType' => $data->deliveryType === DeliveryType::STOP_DESK
                ? 'pickup-point'
                : 'home',
            'description' => $data->productDescription,
            'amount' => $data->price,
            'externalId' => $data->orderId,
        ];

        if ($data->deliveryType === DeliveryType::STOP_DESK && $data->stopDeskId !== null) {
            $payload['hubId'] = (string) $data->stopDeskId;
        }

        if ($data->weight !== null) {
            $payload['weight'] = ['weight' => $data->weight];
        }

        if ($data->length !== null || $data->width !== null || $data->height !== null) {
            $payload['orderedProducts'][0]['length'] = $data->length;
            $payload['orderedProducts'][0]['width'] = $data->width;
            $payload['orderedProducts'][0]['height'] = $data->height;
        }

        $response = $this->post('api/v1/parcels', $payload);
        $parcelId = $response['id'] ?? null;

        if ($parcelId === null) {
            throw new CourierException(
                'ZR Express NEW did not return a parcel ID after creation.',
            );
        }

        return $this->getOrder($parcelId);
    }

    /**
     * Retrieve a parcel by UUID parcel ID or tracking number.
     *
     * UUID   → GET /api/v1/parcels/{uuid}
     * String → GET /api/v1/parcels/{trackingNumber}
     */
    public function getOrder(string $trackingNumber): OrderData
    {
        try {
            $response = $this->get("api/v1/parcels/{$trackingNumber}");

            if (empty($response) || isset($response['title'])) {
                throw new OrderNotFoundException($trackingNumber);
            }

            return $this->hydrateOrder($response);
        } catch (OrderNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new OrderNotFoundException($trackingNumber, $e);
        }
    }

    /**
     * Cancel (delete) a parcel.
     *
     * DELETE /api/v1/parcels/{uuid} — requires the internal UUID.
     * If a tracking number is passed, the UUID is resolved via getOrder() first.
     */
    public function cancelOrder(string $trackingNumber): bool
    {
        $parcelId = $this->isUuid($trackingNumber)
            ? $trackingNumber
            : $this->resolveParcelId($trackingNumber);

        $response = $this->delete("api/v1/parcels/{$parcelId}");

        return isset($response['id']) || empty($response);
    }

    /**
     * Generate an individual shipping label for the given tracking number.
     *
     * Calls POST /api/v1/parcels/labels/individual with the tracking number.
     * Returns a LabelType::HTML_URL pointing to an Azure Blob SAS URL.
     * The file is an HTML document with 4 identical A6 labels on an A4 page.
     *
     * SAS URLs expire after a configured duration — download and cache promptly.
     *
     * Auth note: this endpoint may require Authorization: Bearer in addition
     * to X-Api-Key. The adapter sends both. If authentication fails despite
     * valid credentials, your account may require a JWT from the login endpoint.
     *
     * @throws CourierException if the label could not be generated.
     */
    public function getLabel(string $trackingNumber): LabelData
    {
        $response = $this->post(
            'api/v1/parcels/labels/individual',
            ['trackingNumbers' => [$trackingNumber]],
            ['Authorization' => "Bearer {$this->credentials->apiKey}"],
        );

        $labelFiles = $response['parcelLabelFiles'] ?? [];
        $failed = $response['failedTrackingNumbers'] ?? [];

        if (! empty($failed) && in_array($trackingNumber, (array) $failed, strict: true)) {
            throw new CourierException(
                "ZR Express NEW could not generate a label for [{$trackingNumber}] — parcel not found or territory data missing.",
            );
        }

        if (empty($labelFiles)) {
            throw new CourierException(
                "ZR Express NEW returned no label for [{$trackingNumber}].",
            );
        }

        $file = $labelFiles[0];
        $fileUrl = (string) ($file['fileUrl'] ?? '');

        if ($fileUrl === '') {
            throw new CourierException(
                "ZR Express NEW returned an empty label URL for [{$trackingNumber}].",
            );
        }

        return new LabelData(
            provider: Provider::ZREXPRESS_NEW,
            trackingNumber: $trackingNumber,
            type: LabelType::HTML_URL,
            url: $fileUrl,
        );
    }

    /**
     * Search for a territory UUID by name and level.
     *
     * @param  string  $level  'wilaya' or 'commune'
     * @param  string|null  $parentId  UUID of the parent territory
     * @return string|null UUID of the territory if found
     */
    public function searchTerritory(string $name, string $level = 'wilaya', ?string $parentId = null): ?string
    {
        $filters = [
            [
                'field' => 'name',
                'operator' => 'eq',
                'value' => $name,
            ],
            [
                'field' => 'level',
                'operator' => 'eq',
                'value' => $level,
            ],
        ];

        if ($parentId) {
            $filters[] = [
                'field' => 'parentId',
                'operator' => 'eq',
                'value' => $parentId,
            ];
        }

        $payload = [
            'advancedFilter' => [
                'logic' => 'and',
                'filters' => $filters,
            ],
            'pageNumber' => 1,
            'pageSize' => 1,
        ];

        try {
            $response = $this->post('api/v1/territories/search', $payload);

            return $response['items'][0]['id'] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // Public helpers (ZR Express NEW–specific)
    // -------------------------------------------------------------------------

    // Dynamic resolution via API search preferred over static mapping.

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Parse territory UUIDs from the notes field and auto-resolve city UUID
     * from $toWilayaId when not explicitly provided.
     *
     * Supported note formats:
     *   "zr_district:{uuid}|optional note"                            ← preferred
     *   "zr_city:{uuid}|zr_district:{uuid}|optional note"            ← explicit override
     *
     * @return array{0: string|null, 1: string|null, 2: string|null}
     *                                                               [cityTerritoryId, districtTerritoryId, cleanNote]
     */
    private function parseTerritoryIds(?string $notes, int|string|null $toWilayaId = null): array
    {
        $cityId = null;
        $districtId = null;
        $remaining = [];

        foreach (explode('|', (string) $notes) as $segment) {
            $segment = trim($segment);

            if (str_starts_with($segment, 'zr_city:')) {
                $cityId = trim(substr($segment, strlen('zr_city:')));
            } elseif (str_starts_with($segment, 'zr_district:')) {
                $districtId = trim(substr($segment, strlen('zr_district:')));
            } elseif ($segment !== '') {
                $remaining[] = $segment;
            }
        }

        // Auto-resolve city UUID from wilaya code if not explicitly provided
        if ($cityId === null && $toWilayaId !== null) {
            if ($this->isUuid((string) $toWilayaId)) {
                // If the user supplied a string UUID, use it directly.
                $cityId = (string) $toWilayaId;
                // As per user instruction, fallback districtId to cityId if omitted.
                if ($districtId === null) {
                    $districtId = $cityId;
                }
            }
        }

        return [
            $cityId !== '' ? $cityId : null,
            $districtId !== '' ? $districtId : null,
            $remaining !== [] ? implode(' | ', $remaining) : null,
        ];
    }

    private function resolveParcelId(string $trackingNumber): string
    {
        $order = $this->getOrder($trackingNumber);
        $id = $order->raw['id'] ?? null;

        if ($id === null) {
            throw new OrderNotFoundException($trackingNumber);
        }

        return (string) $id;
    }

    private function isUuid(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $value,
        );
    }

    private function randomUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0F | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3F | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function hydrateOrder(array $raw): OrderData
    {
        $stateName = (string) ($this->dig($raw, 'state', 'name') ?? '');
        $status = $this->normalizeStatus($stateName);

        // cityTerritoryCode is the integer wilaya code (1-58)
        $wilayaCode = (int) ($this->dig($raw, 'deliveryAddress', 'cityTerritoryCode') ?? 0);

        $commune = (string) ($this->dig($raw, 'deliveryAddress', 'district') ?? '');
        $city = (string) ($this->dig($raw, 'deliveryAddress', 'city') ?? '');
        $customerName = (string) ($this->dig($raw, 'customer', 'name') ?? '');
        $phone = (string) ($this->dig($raw, 'customer', 'phone', 'number1') ?? '');

        return new OrderData(
            orderId: (string) ($raw['externalId'] ?? ''),
            trackingNumber: (string) ($raw['trackingNumber'] ?? (string) ($raw['id'] ?? '')),
            provider: Provider::ZREXPRESS_NEW,
            status: $status,
            recipientName: $customerName,
            phone: $phone,
            address: (string) ($this->dig($raw, 'deliveryAddress', 'street') ?? ''),
            toWilayaId: $wilayaCode,
            toCommune: $commune !== '' ? $commune : $city,
            price: (float) ($raw['amount'] ?? 0),
            shippingFee: isset($raw['deliveryPrice']) ? (float) $raw['deliveryPrice'] : null,
            rawStatus: $stateName,
            notes: $this->dig($raw, 'situation', 'name') !== null
                ? (string) $this->dig($raw, 'situation', 'name')
                : null,
            createdAt: $this->parseDate($raw['createdAt'] ?? null),
            updatedAt: $this->parseDate($raw['lastStateUpdateAt'] ?? null),
            raw: $raw,
        );
    }
}
