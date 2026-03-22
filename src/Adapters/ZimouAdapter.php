<?php

declare(strict_types=1);

namespace Uften\Courier\Adapters;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Uften\Courier\Data\CreateOrderData;
use Uften\Courier\Data\Credentials\TokenCredentials;
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
 * Adapter for the Zimou Express API (v3).
 *
 * Zimou Express is a **delivery router**: it accepts a package, assigns it
 * to the best available partner carrier (Yalidine, Maystro, DHD, etc.),
 * and returns both its own tracking code and the sub-carrier's code.
 *
 * Unique fields surfaced in OrderData:
 *   - $order->notes            → the assigned partner carrier name
 *   - $order->raw['delivery_company_tracking_code'] → partner's own tracking code
 *   - $order->raw['tracking_partner_company']       → partner carrier name
 *
 * Auth   : Authorization: Bearer {token}
 * Base   : https://zimou.express/api
 * Docs   : https://zimou.express/api/docs
 *
 * Delivery type mapping (Zimou-specific strings → our DeliveryType enum):
 *   "Express"     → DeliveryType::HOME  (fast, default)
 *   "Flexible"    → DeliveryType::HOME  (standard / cheaper)
 *   "Point relais"→ DeliveryType::STOP_DESK
 *
 * When creating an order, DeliveryType::HOME maps to "Express" by default.
 * To use "Flexible" instead, pass it via CreateOrderData::$notes prefixed
 * with "zimou_delivery_type:Flexible" — the adapter will detect and strip it.
 * Example: $notes = "zimou_delivery_type:Flexible|Leave at door"
 */
final class ZimouAdapter extends AbstractAdapter
{
    // -------------------------------------------------------------------------
    // Status mapping — by integer ID (authoritative) -------------------------
    // -------------------------------------------------------------------------

    /**
     * Maps Zimou status IDs (from /helpers/package-statuses) to canonical statuses.
     * Using IDs is more reliable than names because Zimou may change name casing.
     *
     * @var array<int, TrackingStatus>
     */
    private const array STATUS_ID_MAP = [
        1 => TrackingStatus::PENDING,           // EN PREPARATION
        2 => TrackingStatus::PENDING,           // PRÊT À EXPÉDIER
        3 => TrackingStatus::PICKED_UP,         // EXPEDIE
        4 => TrackingStatus::IN_TRANSIT,        // VERS WILAYA (SAC)
        5 => TrackingStatus::IN_TRANSIT,        // CENTRE (HUB)
        6 => TrackingStatus::IN_TRANSIT,        // TRANSFERT (SAC)
        7 => TrackingStatus::OUT_FOR_DELIVERY,  // SORTIE EN LIVRAISON
        8 => TrackingStatus::DELIVERED,         // LIVRÉ
        9 => TrackingStatus::FAILED_DELIVERY,   // ÉCHEC DE LIVRAISON
        10 => TrackingStatus::EXCEPTION,         // ALERT
        11 => TrackingStatus::FAILED_DELIVERY,   // REPORTER (Date)
        12 => TrackingStatus::PENDING,           // EN ATTENTE
        13 => TrackingStatus::FAILED_DELIVERY,   // TENTATIVE ÉCHOUÉE 1
        14 => TrackingStatus::FAILED_DELIVERY,   // TENTATIVE ÉCHOUÉE 2
        15 => TrackingStatus::FAILED_DELIVERY,   // TENTATIVE ÉCHOUÉE 3
        16 => TrackingStatus::RETURNING,         // RETOUR VERS CENTRE
        17 => TrackingStatus::RETURNING,         // RETOURNEE AU CENTRE
        18 => TrackingStatus::READY_FOR_PICKUP,  // RETOUR A RETIRER (at desk for sender)
        19 => TrackingStatus::RETURNING,         // RETOUR VERS VENDEUR (SAC)
        20 => TrackingStatus::RETURNED,          // RETOURNEE AU VENDEUR (SAC)
        21 => TrackingStatus::IN_TRANSIT,        // SOCIETE PARTENAIRE (handed to partner)
        22 => TrackingStatus::FAILED_DELIVERY,   // REFUSÉ
        23 => TrackingStatus::PICKED_UP,         // PICKUP
        24 => TrackingStatus::IN_TRANSIT,        // En Dispatche
        25 => TrackingStatus::IN_TRANSIT,        // Dispatché
        26 => TrackingStatus::PENDING,           // WAREHOUSE EN PREPARATION
        27 => TrackingStatus::EXCEPTION,         // WAREHOUSE HORS STOCK
        28 => TrackingStatus::PENDING,           // WAREHOUSE PRET
        29 => TrackingStatus::RETURNED,          // WAREHOUSE RETOURNÉE
        30 => TrackingStatus::CANCELLED,         // WAREHOUSE DEMANDE ANNULATION
        31 => TrackingStatus::CANCELLED,         // WAREHOUSE ANNULÉE
        32 => TrackingStatus::EXCEPTION,         // WAREHOUSE RETOURNÉE ENDOMAGÉ
        33 => TrackingStatus::PENDING,           // PAS ENCORE RAMASSÉ
        34 => TrackingStatus::PENDING,           // DROPSHIP EN PREPARATION
        35 => TrackingStatus::PENDING,           // DROPSHIP PRET
        36 => TrackingStatus::READY_FOR_PICKUP,  // ECHANGE A RETIRER
        37 => TrackingStatus::RETURNED,          // DROPSHIPS RETOURNEÉ
        38 => TrackingStatus::EXCEPTION,         // DROPSHIPS RETOURNEÉ ENDOMMAGÉ
        39 => TrackingStatus::PICKED_UP,         // ECHANGE COLLECTÉ
        40 => TrackingStatus::IN_TRANSIT,        // AU CENTRE
        41 => TrackingStatus::CANCELLED,         // DROPSHIPS DEMANDE ANNULATION
        42 => TrackingStatus::CANCELLED,         // DROPSHIPS ANNULÉE
        43 => TrackingStatus::PENDING,           // FRET EN PREPARATION
        44 => TrackingStatus::PENDING,           // FRET PRET
        45 => TrackingStatus::CANCELLED,         // FRET DEMANDE ANNULATION
        46 => TrackingStatus::CANCELLED,         // FRET ANNULÉE
        83 => TrackingStatus::EXCEPTION,        // Bloqué
        112 => TrackingStatus::IN_TRANSIT,       // En localisation
        113 => TrackingStatus::OUT_FOR_DELIVERY, // Prêt pour livreur
        114 => TrackingStatus::IN_TRANSIT,       // En Transit
        115 => TrackingStatus::IN_TRANSIT,       // En Hub
        116 => TrackingStatus::RETURNING,        // Retour (Station)
        118 => TrackingStatus::EXCEPTION,        // Perdu
    ];

    /**
     * Status name fallback map (lowercase, for the StatusNormalizer contract).
     * Used when status_id is absent from the response.
     *
     * @var array<string, TrackingStatus>
     */
    private const array STATUS_NAME_MAP = [
        'en preparation' => TrackingStatus::PENDING,
        'prêt à expédier' => TrackingStatus::PENDING,
        'en attente' => TrackingStatus::PENDING,
        'pas encore ramassé' => TrackingStatus::PENDING,
        'dropship en preparation' => TrackingStatus::PENDING,
        'dropship pret' => TrackingStatus::PENDING,
        'warehouse en preparation' => TrackingStatus::PENDING,
        'warehouse pret' => TrackingStatus::PENDING,
        'fret en preparation' => TrackingStatus::PENDING,
        'fret pret' => TrackingStatus::PENDING,
        'expedie' => TrackingStatus::PICKED_UP,
        'pickup' => TrackingStatus::PICKED_UP,
        'echange collecté' => TrackingStatus::PICKED_UP,
        'vers wilaya ( sac )' => TrackingStatus::IN_TRANSIT,
        'centre ( hub )' => TrackingStatus::IN_TRANSIT,
        'transfert (sac)' => TrackingStatus::IN_TRANSIT,
        'societe partenaire' => TrackingStatus::IN_TRANSIT,
        'en dispatche' => TrackingStatus::IN_TRANSIT,
        'dispatché' => TrackingStatus::IN_TRANSIT,
        'au centre' => TrackingStatus::IN_TRANSIT,
        'en localisation' => TrackingStatus::IN_TRANSIT,
        'en transit' => TrackingStatus::IN_TRANSIT,
        'en hub' => TrackingStatus::IN_TRANSIT,
        'sortie en livraison' => TrackingStatus::OUT_FOR_DELIVERY,
        'prêt pour livreur' => TrackingStatus::OUT_FOR_DELIVERY,
        'livré' => TrackingStatus::DELIVERED,
        'échec de livraison' => TrackingStatus::FAILED_DELIVERY,
        'reporter (date)' => TrackingStatus::FAILED_DELIVERY,
        'tentative échouée 1' => TrackingStatus::FAILED_DELIVERY,
        'tentative échouée 2' => TrackingStatus::FAILED_DELIVERY,
        'tentative échouée 3' => TrackingStatus::FAILED_DELIVERY,
        'refusé' => TrackingStatus::FAILED_DELIVERY,
        'retour vers centre' => TrackingStatus::RETURNING,
        'retournee au centre' => TrackingStatus::RETURNING,
        'retour vers vendeur (sac)' => TrackingStatus::RETURNING,
        'retour (station)' => TrackingStatus::RETURNING,
        'retournee au vendeur (sac)' => TrackingStatus::RETURNED,
        'warehouse retournée' => TrackingStatus::RETURNED,
        'dropships retourneé' => TrackingStatus::RETURNED,
        'warehouse demande annulation' => TrackingStatus::CANCELLED,
        'warehouse annulée' => TrackingStatus::CANCELLED,
        'dropships demande annulation' => TrackingStatus::CANCELLED,
        'dropships annulée' => TrackingStatus::CANCELLED,
        'fret demande annulation' => TrackingStatus::CANCELLED,
        'fret annulée' => TrackingStatus::CANCELLED,
        'retour a retirer' => TrackingStatus::READY_FOR_PICKUP,
        'echange a retirer' => TrackingStatus::READY_FOR_PICKUP,
        'alert' => TrackingStatus::EXCEPTION,
        'warehouse hors stock' => TrackingStatus::EXCEPTION,
        'warehouse retournée endomagé' => TrackingStatus::EXCEPTION,
        'dropships retourneé endommagé' => TrackingStatus::EXCEPTION,
        'bloqué' => TrackingStatus::EXCEPTION,
        'perdu' => TrackingStatus::EXCEPTION,
    ];

    // -------------------------------------------------------------------------
    // Zimou delivery type strings (used in the API request payload)
    // -------------------------------------------------------------------------
    private const string DELIVERY_EXPRESS = 'Express';

    private const string DELIVERY_FLEXIBLE = 'Flexible';

    private const string DELIVERY_POINT_RELAIS = 'Point relais';

    public function __construct(
        private readonly TokenCredentials $credentials,
        ?Client $httpClient = null,
    ) {
        parent::__construct(
            baseUrl: Provider::ZIMOU->baseUrl(),
            defaultHeaders: [
                'Authorization' => "Bearer {$this->credentials->token}",
            ],
            httpClient: $httpClient,
        );

        $this->providerEnum = Provider::ZIMOU;
    }

    // -------------------------------------------------------------------------
    // StatusNormalizer — try ID first, fall back to name
    // -------------------------------------------------------------------------

    public function normalizeStatus(string $rawStatus): TrackingStatus
    {
        // If the caller passes a numeric string (status_id as string)
        if (ctype_digit($rawStatus)) {
            return self::STATUS_ID_MAP[(int) $rawStatus] ?? TrackingStatus::UNKNOWN;
        }

        return self::STATUS_NAME_MAP[mb_strtolower(trim($rawStatus))] ?? TrackingStatus::UNKNOWN;
    }

    /**
     * Normalise directly from the integer status_id.
     * More reliable than normalising by name.
     */
    public function normalizeStatusById(int $statusId): TrackingStatus
    {
        return self::STATUS_ID_MAP[$statusId] ?? TrackingStatus::UNKNOWN;
    }

    // -------------------------------------------------------------------------
    // ProviderAdapter implementation
    // -------------------------------------------------------------------------

    public function testCredentials(): bool
    {
        try {
            $this->get('v3/user');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function getRates(?int $fromWilayaId = null, ?int $toWilayaId = null): array
    {
        $raw = $this->get('v3/my/prices');

        $rows = $raw['data'] ?? $raw;

        if (! is_array($rows) || empty($rows)) {
            return [];
        }

        $rates = [];

        foreach ($rows as $item) {
            if (! is_array($item)) {
                continue;
            }

            // Zimou returns prices keyed by wilaya — shape varies per account config.
            // We surface what we can and leave the rest in RateData.
            $wilayaId = (int) ($item['wilaya_id'] ?? $item['wilaya'] ?? 0);
            $wilayaName = (string) ($item['wilaya_name'] ?? $item['wilaya'] ?? '');

            $rates[] = new RateData(
                provider: Provider::ZIMOU,
                toWilayaId: $wilayaId,
                toWilayaName: $wilayaName,
                homeDeliveryPrice: (float) ($item['express_price']
                    ?? $item['home_price']
                    ?? $item['price']
                    ?? 0),
                stopDeskPrice: (float) ($item['stopdesk_price']
                    ?? $item['point_relais_price']
                    ?? $item['price']
                    ?? 0),
                deliveryType: DeliveryType::HOME,
                fromWilayaId: $fromWilayaId,
            );
        }

        return $rates;
    }

    public function getCreateOrderValidationRules(): array
    {
        return [
            'order_id' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'to_wilaya_id' => ['required', 'integer', 'between:1,58'],
            'to_commune' => ['required', 'string'],
            'product_description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'delivery_type' => ['required', 'integer', 'in:1,2'],
            // Zimou-specific optional fields
            'phone_alt' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:500'],
            'weight' => ['nullable', 'numeric', 'min:1'],
            'length' => ['nullable', 'numeric', 'min:0'],
            'width' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'stop_desk_id' => ['nullable', 'integer'],
        ];
    }

    public function createOrder(CreateOrderData $data): OrderData
    {
        [$zimouDeliveryType, $cleanNotes] = $this->resolveDeliveryType($data);

        $payload = [
            // Package type — "ecommerce" covers standard COD shipments
            'type' => 'ecommerce',
            'name' => $data->productDescription,
            'client_first_name' => $data->firstName,
            'client_last_name' => $data->lastName,
            'client_phone' => $data->phone,
            'address' => $data->address,
            'order_id' => $data->orderId,
            'price' => (string) $data->price,
            'free_delivery' => $data->freeShipping ? 'true' : 'false',
            'delivery_type' => $zimouDeliveryType,
            'wilaya' => (string) $data->toWilayaId,
            'commune' => $data->toCommune,
            'can_be_opened' => true,
        ];

        if ($data->phoneAlt !== null) {
            $payload['client_phone2'] = $data->phoneAlt;
        }

        if ($cleanNotes !== null) {
            $payload['observation'] = $cleanNotes;
        }

        if ($data->weight !== null) {
            $payload['weight'] = $data->weight;
        }

        if ($data->stopDeskId !== null) {
            $payload['office_id'] = $data->stopDeskId;
        }

        if ($data->hasExchange && $data->exchangeProduct !== null) {
            $payload['returned_product'] = $data->exchangeProduct;
        }

        if ($data->length !== null || $data->width !== null || $data->height !== null) {
            $payload['volumetric'] = [
                'length' => $data->length,
                'width' => $data->width,
                'height' => $data->height,
            ];
        }

        $response = $this->post('v3/packages', $payload);

        // Zimou returns error:1 even on HTTP 201 for validation failures
        if (isset($response['error']) && (int) $response['error'] === 1) {
            throw new CourierException(
                'Zimou Express rejected the order: '.($response['message'] ?? 'Unknown error'),
            );
        }

        $packageData = $response['data'] ?? $response;

        return $this->hydrateOrder($packageData);
    }

    /**
     * Retrieve an order by its Zimou tracking code or integer package ID.
     *
     * Zimou assigns two tracking codes after creating an order:
     *  - tracking_code                    → Zimou's own code (use this as $trackingNumber)
     *  - delivery_company_tracking_code   → the assigned sub-carrier's code
     *
     * If `$trackingNumber` is numeric it is treated as the internal Zimou
     * package ID and `GET /v3/packages/{id}` is called.
     * Otherwise `GET /v3/packages/status?packages[]={tracking_code}` is used.
     */
    public function getOrder(string $trackingNumber): OrderData
    {
        if (ctype_digit($trackingNumber)) {
            // Integer ID path — most efficient
            try {
                $response = $this->get("v3/packages/{$trackingNumber}");
                $data = $response['data'] ?? $response;

                if (empty($data) || isset($data['message'])) {
                    throw new OrderNotFoundException($trackingNumber);
                }

                return $this->hydrateOrder($data);
            } catch (OrderNotFoundException $e) {
                throw $e;
            } catch (\Throwable $e) {
                throw new OrderNotFoundException($trackingNumber, $e);
            }
        }

        // Tracking-code path — Zimou status endpoint
        $response = $this->get('v3/packages/status', [
            'packages' => [$trackingNumber],
        ]);

        // Response is an object keyed by tracking code
        $data = $response[$trackingNumber]
            ?? $response['data'][$trackingNumber]
            ?? null;

        if ($data === null || empty($data)) {
            throw new OrderNotFoundException($trackingNumber);
        }

        // The status endpoint returns a lighter payload — enrich with
        // what we have and mark the rest as unavailable.
        return $this->hydrateOrderFromStatus($trackingNumber, $data);
    }

    /**
     * Retrieve the shipping label PDF for a package.
     *
     * Zimou returns the PDF as a raw string from POST /v3/packages/labels.
     * We base64-encode it and return a PDF_BASE64 LabelData.
     */
    public function getLabel(string $trackingNumber): LabelData
    {
        $rawPdf = $this->requestRaw('POST', 'v3/packages/labels', [
            RequestOptions::JSON => [
                'packages' => [$trackingNumber],
            ],
        ]);

        if ($rawPdf === '' || $rawPdf === 'null') {
            throw new CourierException(
                "Zimou Express returned an empty label for [{$trackingNumber}].",
            );
        }

        // Zimou may return the PDF as raw bytes OR as a JSON-wrapped base64 string
        if (str_starts_with(ltrim($rawPdf), '{') || str_starts_with(ltrim($rawPdf), '"')) {
            // JSON-wrapped — decode and use as-is
            $decoded = json_decode($rawPdf, associative: true);
            $b64 = is_string($decoded) ? $decoded : ($decoded['data'] ?? $rawPdf);

            return new LabelData(
                provider: Provider::ZIMOU,
                trackingNumber: $trackingNumber,
                type: LabelType::PDF_BASE64,
                base64: $b64,
            );
        }

        // Raw binary PDF bytes
        return LabelData::fromBase64(
            Provider::ZIMOU,
            $trackingNumber,
            base64_encode($rawPdf),
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Determine the Zimou delivery type string and extract any clean notes.
     *
     * Convention for "Flexible" delivery:
     *   Set CreateOrderData::$notes = "zimou_delivery_type:Flexible|Your note here"
     *
     * @return array{0: string, 1: string|null} [zimouDeliveryType, cleanNotes]
     */
    private function resolveDeliveryType(CreateOrderData $data): array
    {
        // Stop desk always maps to "Point relais"
        if ($data->deliveryType === DeliveryType::STOP_DESK) {
            return [self::DELIVERY_POINT_RELAIS, $data->notes];
        }

        // Check if the caller embeds a Zimou-specific type in the notes field
        $notes = $data->notes;
        if ($notes !== null && str_starts_with($notes, 'zimou_delivery_type:')) {
            $parts = explode('|', $notes, 2);
            $typeHint = trim(str_replace('zimou_delivery_type:', '', $parts[0]));
            $cleanedNotes = isset($parts[1]) && $parts[1] !== '' ? $parts[1] : null;

            $zimouType = match ($typeHint) {
                'Flexible' => self::DELIVERY_FLEXIBLE,
                'Point relais' => self::DELIVERY_POINT_RELAIS,
                default => self::DELIVERY_EXPRESS,
            };

            return [$zimouType, $cleanedNotes];
        }

        // Default HOME → Express
        return [self::DELIVERY_EXPRESS, $notes];
    }

    /**
     * Build OrderData from a full PackageResource response.
     */
    private function hydrateOrder(array $raw): OrderData
    {
        $statusId = isset($raw['status_id']) ? (int) $raw['status_id'] : null;
        $rawStatus = (string) ($raw['status_name'] ?? ($statusId !== null ? (string) $statusId : ''));
        $status = $statusId !== null
            ? $this->normalizeStatusById($statusId)
            : $this->normalizeStatus($rawStatus);

        // Wilaya info lives nested inside commune
        $wilayaId = (int) ($this->dig($raw, 'commune', 'wilaya_id') ?? $raw['wilaya_id'] ?? 0);

        // Zimou-specific: the sub-carrier name is very useful context
        $partnerName = $raw['tracking_partner_company'] ?? null;
        $partnerTracking = $raw['delivery_company_tracking_code'] ?? null;

        $notesLines = array_filter([
            $raw['observation'] ?? null,
            $partnerName ? "Via: {$partnerName}" : null,
            $partnerTracking ? "Partner tracking: {$partnerTracking}" : null,
        ]);

        return new OrderData(
            orderId: (string) ($raw['order_id'] ?? ''),
            trackingNumber: (string) ($raw['tracking_code'] ?? (string) ($raw['id'] ?? '')),
            provider: Provider::ZIMOU,
            status: $status,
            recipientName: trim(($raw['client_first_name'] ?? '').' '.($raw['client_last_name'] ?? '')),
            phone: (string) ($raw['client_phone'] ?? ''),
            address: (string) ($raw['address'] ?? ''),
            toWilayaId: $wilayaId,
            toCommune: (string) ($this->dig($raw, 'commune', 'name') ?? $raw['commune'] ?? ''),
            price: (float) ($raw['price'] ?? 0),
            shippingFee: isset($raw['delivery_price']) ? (float) $raw['delivery_price'] : null,
            rawStatus: $rawStatus,
            notes: $notesLines !== [] ? implode(' | ', $notesLines) : null,
            createdAt: $this->parseDate($raw['created_at'] ?? null),
            updatedAt: $this->parseDate($raw['updated_at'] ?? null),
            raw: $raw,
        );
    }

    /**
     * Build a lighter OrderData from the /packages/status response.
     * The status endpoint returns less detail than the full package resource.
     */
    private function hydrateOrderFromStatus(string $trackingNumber, array $raw): OrderData
    {
        $statusId = isset($raw['status_id']) ? (int) $raw['status_id'] : null;
        $rawStatus = (string) ($raw['status_name'] ?? ($statusId !== null ? (string) $statusId : ''));
        $status = $statusId !== null
            ? $this->normalizeStatusById($statusId)
            : $this->normalizeStatus($rawStatus);

        return new OrderData(
            orderId: (string) ($raw['order_id'] ?? ''),
            trackingNumber: $trackingNumber,
            provider: Provider::ZIMOU,
            status: $status,
            recipientName: trim(($raw['client_first_name'] ?? '').' '.($raw['client_last_name'] ?? '')),
            phone: (string) ($raw['client_phone'] ?? ''),
            address: (string) ($raw['address'] ?? ''),
            toWilayaId: (int) ($raw['wilaya_id'] ?? 0),
            toCommune: (string) ($raw['commune'] ?? ''),
            price: (float) ($raw['price'] ?? 0),
            rawStatus: $rawStatus,
            notes: isset($raw['tracking_partner_company'])
                ? "Via: {$raw['tracking_partner_company']}" : null,
            createdAt: $this->parseDate($raw['created_at'] ?? null),
            updatedAt: $this->parseDate($raw['updated_at'] ?? null),
            raw: $raw,
        );
    }
}
