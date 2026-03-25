<?php

declare(strict_types=1);

namespace Uften\Courier\Adapters;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
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
use Uften\Courier\Exceptions\UnsupportedOperationException;

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
 * -------------------------------------------------------------------------
 * Territory UUIDs convention
 * -------------------------------------------------------------------------
 * ZR Express NEW uses UUID territory IDs for delivery address.
 * The adapter can resolve the city UUID automatically from the integer
 * `toWilayaId` field using the built-in WILAYA_UUID_MAP (all 54 wilayas).
 * Only the district (commune) UUID still needs to be supplied via notes:
 *
 *   "zr_district:{districtUUID}|Optional real note"
 *
 * If you also need to override the city UUID (e.g. for a commune that
 * belongs to a different wilaya than the code suggests), you can provide:
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
        'commande_recue'        => TrackingStatus::PENDING,
        'orderreceived'         => TrackingStatus::PENDING,
        'en_traitement'         => TrackingStatus::PENDING,
        'inprocessing'          => TrackingStatus::PENDING,
        'appel_confirmation'    => TrackingStatus::PENDING,
        'confirmationcall'      => TrackingStatus::PENDING,
        'commande_confirmee'    => TrackingStatus::PENDING,
        'orderconfirmed'        => TrackingStatus::PENDING,
        'en_preparation'        => TrackingStatus::PENDING,
        'inpreparation'         => TrackingStatus::PENDING,

        // ── Picked up ────────────────────────────────────────────────────────
        'pret_a_expedier'       => TrackingStatus::PICKED_UP,
        'readytodispatch'       => TrackingStatus::PICKED_UP,

        // ── In transit ───────────────────────────────────────────────────────
        'confirme_au_bureau'    => TrackingStatus::IN_TRANSIT,
        'confirmedatbranch'     => TrackingStatus::IN_TRANSIT,
        'dispatch'              => TrackingStatus::IN_TRANSIT,
        'dispatched'            => TrackingStatus::IN_TRANSIT,
        'vers_wilaya'           => TrackingStatus::IN_TRANSIT,
        'interwilayatransit'    => TrackingStatus::IN_TRANSIT,
        'en_livraison'          => TrackingStatus::IN_TRANSIT,
        'indelivery'            => TrackingStatus::IN_TRANSIT,

        // ── Out for delivery ─────────────────────────────────────────────────
        'sortie_en_livraison'   => TrackingStatus::OUT_FOR_DELIVERY,
        'outfordelivery'        => TrackingStatus::OUT_FOR_DELIVERY,

        // ── Delivered ────────────────────────────────────────────────────────
        'livre'                 => TrackingStatus::DELIVERED,
        'delivered'             => TrackingStatus::DELIVERED,
        'encaisse'              => TrackingStatus::DELIVERED,
        'collected'             => TrackingStatus::DELIVERED,
        'recouvert'             => TrackingStatus::DELIVERED,

        // ── Failed delivery ──────────────────────────────────────────────────
        'echec_livraison'       => TrackingStatus::FAILED_DELIVERY,
        'faileddelivery'        => TrackingStatus::FAILED_DELIVERY,
        'delivery_failed'       => TrackingStatus::FAILED_DELIVERY,
        'commande_annulee'      => TrackingStatus::FAILED_DELIVERY,
        'orderrefused'          => TrackingStatus::FAILED_DELIVERY,

        // ── Returning ────────────────────────────────────────────────────────
        'retour'                => TrackingStatus::RETURNING,
        'returning'             => TrackingStatus::RETURNING,
        'en_retour'             => TrackingStatus::RETURNING,
        'inreturn'              => TrackingStatus::RETURNING,

        // ── Returned ─────────────────────────────────────────────────────────
        'retourne'              => TrackingStatus::RETURNED,
        'returned'              => TrackingStatus::RETURNED,
        'retour_confirme'       => TrackingStatus::RETURNED,
        'returnconfirmed'       => TrackingStatus::RETURNED,
        'reinjecte_stock'       => TrackingStatus::RETURNED,

        // ── Cancelled ────────────────────────────────────────────────────────
        'annule'                => TrackingStatus::CANCELLED,
        'cancelled'             => TrackingStatus::CANCELLED,

        // ── Ready for pickup (stop desk) ─────────────────────────────────────
        'disponible_bureau'     => TrackingStatus::READY_FOR_PICKUP,
        'readyforpickup'        => TrackingStatus::READY_FOR_PICKUP,
        'en_attente_client'     => TrackingStatus::READY_FOR_PICKUP,
        'waitingclient'         => TrackingStatus::READY_FOR_PICKUP,

        // ── Exception ────────────────────────────────────────────────────────
        'en_attente_echange'    => TrackingStatus::EXCEPTION,
        'remboursement'         => TrackingStatus::EXCEPTION,
    ];

    // -------------------------------------------------------------------------
    // Wilaya UUID map  (integer code → ZR Express territory UUID)
    // Source: GET /api/v1/territories/search — static snapshot from Wilayas.json
    // Codes match standard Algerian wilaya numbering (1-58, with gaps).
    // -------------------------------------------------------------------------

    /** @var array<int, string> */
    private const array WILAYA_UUID_MAP = [
        1  => '6e978fc5-f20a-4b5f-9adf-61dd21a7672a', // Adrar
        2  => '981f136a-996f-463e-a536-8e643daab193', // Chlef
        3  => '00b5ef4b-ae2e-4b7f-bd26-70c1a376b69b', // Laghouat
        4  => '37c70742-df6b-4019-981a-a16a29a14748', // Oum El Bouaghi
        5  => 'a8c05822-e30a-4d5a-bcb3-3b3bb23c079b', // Batna
        6  => '295585ad-4cf4-4b7e-b276-9bb62d019749', // Bejaia
        7  => '796e70df-1102-44da-9582-2da66ead2ba6', // Biskra
        8  => 'e740c188-2bbc-4206-8999-302b17dc0e4b', // Bechar
        9  => 'a7e764cf-e9ca-4c1f-8232-89852d102aec', // Blida
        10 => 'a1f0229c-4f34-40aa-9238-fadde6757cba', // Bouira
        11 => '38560f06-e049-4fd2-9664-a655e552b517', // Tamanrasset
        12 => '5afdfab6-e505-4691-abc7-5e8bd79afad5', // Tebessa
        13 => '53c9e062-9c4e-4c77-8b71-55eabf887f83', // Tlemcen
        14 => 'ada5bb27-ffe5-4977-a917-3105c2b3d9c6', // Tiaret
        15 => '5bef8e95-fad8-4a15-95f0-8d6f5c80f69e', // Tizi Ouzou
        16 => 'd134c182-7dac-4655-9d9b-bbdb62aa2ec4', // Alger
        17 => '9ee8eac2-77e5-4d70-ac49-bde455d06bee', // Djelfa
        18 => 'dc851e52-55b2-4beb-a7f1-79d4e73e9458', // Jijel
        19 => '56ee938d-7887-408e-8731-364d07ad3594', // Setif
        20 => '27b2042a-77f8-4c91-b62d-60934fa0daca', // Saida
        21 => 'a9df7e26-1086-4319-8a93-19969c99c89b', // Skikda
        22 => '2cec2b2a-cc37-480a-9183-59fdfdb65cd4', // Sidi Bel Abbes
        23 => '3fd318e8-7c24-480c-a106-21f6c842583d', // Annaba
        24 => '2d1e61ff-e2af-4b4d-a592-0a6436c5fffd', // Guelma
        25 => 'e9a1e9cf-8475-4768-94cc-0888d094ff47', // Constantine
        26 => '0e0f2d43-6d78-47dd-8bb7-0f2771cb97ff', // Medea
        27 => 'd7175ca6-6dd7-4dfb-a399-d388e782473a', // Mostaganem
        28 => '75ca308d-ab36-44e2-9702-2e2300a57b8c', // MSila
        29 => 'a17a6482-3f48-4948-aaf2-8a653c4c1110', // Mascara
        30 => 'ada333a0-708d-476e-a97d-fd70fe661b09', // Ouargla
        31 => 'e772eb46-276a-4f41-bae7-3b67e1bdc616', // Oran
        32 => 'dca8b699-ce8b-4ad7-b8f2-560e63911383', // El Bayadh
        34 => '80d1b557-03b2-4073-a8c2-89a8712a7fc8', // Bordj Bou Arreridj
        35 => 'f823492c-f79d-4c2d-befe-933bf9917a65', // Boumerdes
        36 => 'e6f4b09c-f63e-42af-92bc-dab9b422c34d', // El Tarf
        38 => 'fb1a9f7a-81a2-4825-af92-79f9d187637f', // Tissemsilt
        39 => 'cd82549a-b1f7-48c1-9a25-2f3f05b80b1d', // El Oued
        40 => 'd4549528-8327-4a3f-9732-5a5462c84b8d', // Khenchela
        41 => '56d30b7a-465a-462c-bc2a-3e132c89be63', // Souk Ahras
        42 => '1435179a-6dbb-4d9c-a186-c521b2a57319', // Tipaza
        43 => '0c8476c5-bbe4-46e4-80e5-67d3501195cc', // Mila
        44 => '8d2d130f-460c-4867-85ef-641341a4d586', // Ain Defla
        45 => 'ecdf0888-0470-4b2f-beb8-24c99b6fc9cb', // Naama
        46 => 'fc460ec5-3e71-489c-b95b-e5301ea68341', // Ain Temouchent
        47 => 'e7b51620-74f4-4748-85c5-216fb9b01b03', // Ghardaia
        48 => 'ad58c5ee-868d-4acb-8f03-409f97a10370', // Relizane
        49 => 'bcb30485-37b5-4135-a508-acad8a8a9cf8', // Timimoun
        51 => '0f2dab00-094c-412c-a7d0-ebd0268d3d3c', // Ouled Djellal
        52 => 'ba12c65c-de9e-4f30-a449-6ba0b27dd7d7', // Beni Abbes
        53 => '7c752560-8412-4e11-8c75-ed7cd9c22be2', // In Salah
        54 => 'f30136dc-3012-4ac7-912c-33eab37393a9', // In Guezzam
        55 => '442d8a1c-2e12-4a8a-9c7e-8618aac20280', // Touggourt
        57 => 'eabb6505-5eef-479f-b6a3-36ba282d5237', // El Meghaier
        58 => '3d19d427-08f3-492c-a1d0-e7ace3516ed2', // El Menia
    ];

    /**
     * Reverse map: territory UUID → integer wilaya code.
     * Used when hydrating rates/orders from the API response.
     *
     * @var array<string, int>
     */
    private const array WILAYA_CODE_MAP = [
        '6e978fc5-f20a-4b5f-9adf-61dd21a7672a' => 1,
        '981f136a-996f-463e-a536-8e643daab193' => 2,
        '00b5ef4b-ae2e-4b7f-bd26-70c1a376b69b' => 3,
        '37c70742-df6b-4019-981a-a16a29a14748' => 4,
        'a8c05822-e30a-4d5a-bcb3-3b3bb23c079b' => 5,
        '295585ad-4cf4-4b7e-b276-9bb62d019749' => 6,
        '796e70df-1102-44da-9582-2da66ead2ba6' => 7,
        'e740c188-2bbc-4206-8999-302b17dc0e4b' => 8,
        'a7e764cf-e9ca-4c1f-8232-89852d102aec' => 9,
        'a1f0229c-4f34-40aa-9238-fadde6757cba' => 10,
        '38560f06-e049-4fd2-9664-a655e552b517' => 11,
        '5afdfab6-e505-4691-abc7-5e8bd79afad5' => 12,
        '53c9e062-9c4e-4c77-8b71-55eabf887f83' => 13,
        'ada5bb27-ffe5-4977-a917-3105c2b3d9c6' => 14,
        '5bef8e95-fad8-4a15-95f0-8d6f5c80f69e' => 15,
        'd134c182-7dac-4655-9d9b-bbdb62aa2ec4' => 16,
        '9ee8eac2-77e5-4d70-ac49-bde455d06bee' => 17,
        'dc851e52-55b2-4beb-a7f1-79d4e73e9458' => 18,
        '56ee938d-7887-408e-8731-364d07ad3594' => 19,
        '27b2042a-77f8-4c91-b62d-60934fa0daca' => 20,
        'a9df7e26-1086-4319-8a93-19969c99c89b' => 21,
        '2cec2b2a-cc37-480a-9183-59fdfdb65cd4' => 22,
        '3fd318e8-7c24-480c-a106-21f6c842583d' => 23,
        '2d1e61ff-e2af-4b4d-a592-0a6436c5fffd' => 24,
        'e9a1e9cf-8475-4768-94cc-0888d094ff47' => 25,
        '0e0f2d43-6d78-47dd-8bb7-0f2771cb97ff' => 26,
        'd7175ca6-6dd7-4dfb-a399-d388e782473a' => 27,
        '75ca308d-ab36-44e2-9702-2e2300a57b8c' => 28,
        'a17a6482-3f48-4948-aaf2-8a653c4c1110' => 29,
        'ada333a0-708d-476e-a97d-fd70fe661b09' => 30,
        'e772eb46-276a-4f41-bae7-3b67e1bdc616' => 31,
        'dca8b699-ce8b-4ad7-b8f2-560e63911383' => 32,
        '80d1b557-03b2-4073-a8c2-89a8712a7fc8' => 34,
        'f823492c-f79d-4c2d-befe-933bf9917a65' => 35,
        'e6f4b09c-f63e-42af-92bc-dab9b422c34d' => 36,
        'fb1a9f7a-81a2-4825-af92-79f9d187637f' => 38,
        'cd82549a-b1f7-48c1-9a25-2f3f05b80b1d' => 39,
        'd4549528-8327-4a3f-9732-5a5462c84b8d' => 40,
        '56d30b7a-465a-462c-bc2a-3e132c89be63' => 41,
        '1435179a-6dbb-4d9c-a186-c521b2a57319' => 42,
        '0c8476c5-bbe4-46e4-80e5-67d3501195cc' => 43,
        '8d2d130f-460c-4867-85ef-641341a4d586' => 44,
        'ecdf0888-0470-4b2f-beb8-24c99b6fc9cb' => 45,
        'fc460ec5-3e71-489c-b95b-e5301ea68341' => 46,
        'e7b51620-74f4-4748-85c5-216fb9b01b03' => 47,
        'ad58c5ee-868d-4acb-8f03-409f97a10370' => 48,
        'bcb30485-37b5-4135-a508-acad8a8a9cf8' => 49,
        '0f2dab00-094c-412c-a7d0-ebd0268d3d3c' => 51,
        'ba12c65c-de9e-4f30-a449-6ba0b27dd7d7' => 52,
        '7c752560-8412-4e11-8c75-ed7cd9c22be2' => 53,
        'f30136dc-3012-4ac7-912c-33eab37393a9' => 54,
        '442d8a1c-2e12-4a8a-9c7e-8618aac20280' => 55,
        'eabb6505-5eef-479f-b6a3-36ba282d5237' => 57,
        '3d19d427-08f3-492c-a1d0-e7ace3516ed2' => 58,
    ];

    public function __construct(
        private readonly ZrExpressNewCredentials $credentials,
        ?Client $httpClient = null,
    ) {
        parent::__construct(
            baseUrl: Provider::ZREXPRESS_NEW->baseUrl(),
            defaultHeaders: [
                'X-Tenant'  => $this->credentials->tenantId,
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
                'pageSize'   => 1,
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
     * and Unknown entries are skipped since they cannot be reliably mapped
     * to an integer wilaya code.
     *
     * @return list<RateData>
     */
    public function getRates(?int $fromWilayaId = null, ?int $toWilayaId = null): array
    {
        $response = $this->get('api/v1/delivery-pricing/rates');
        $rates    = $response['rates'] ?? [];

        if (!is_array($rates) || empty($rates)) {
            return [];
        }

        $result = [];

        foreach ($rates as $rate) {
            if (!is_array($rate)) {
                continue;
            }

            // Skip commune and Unknown levels — they can't map to a wilaya code
            $level = mb_strtolower((string) ($rate['toTerritoryLevel'] ?? ''));
            if ($level !== 'wilaya') {
                continue;
            }

            // Resolve the integer wilaya code
            $wilayaCode = isset($rate['toTerritoryCode']) && $rate['toTerritoryCode'] !== null
                ? (int) $rate['toTerritoryCode']
                : self::WILAYA_CODE_MAP[$rate['toTerritoryId'] ?? ''] ?? 0;

            if ($wilayaCode === 0) {
                continue; // Could not resolve wilaya code — skip
            }

            // Apply optional filter
            if ($toWilayaId !== null && $wilayaCode !== $toWilayaId) {
                continue;
            }

            // Extract home and pickup-point prices from the deliveryPrices array
            $homePrice    = 0.0;
            $stopDeskPrice = 0.0;

            foreach ($rate['deliveryPrices'] ?? [] as $dp) {
                $type  = mb_strtolower((string) ($dp['deliveryType'] ?? ''));
                $price = (float) ($dp['price'] ?? 0);

                if ($type === 'home') {
                    $homePrice = $price;
                } elseif ($type === 'pickup-point') {
                    $stopDeskPrice = $price;
                }
            }

            $result[] = new RateData(
                provider:          Provider::ZREXPRESS_NEW,
                toWilayaId:        $wilayaCode,
                toWilayaName:      (string) ($rate['toTerritoryName'] ?? ''),
                homeDeliveryPrice: $homePrice,
                stopDeskPrice:     $stopDeskPrice,
                deliveryType:      DeliveryType::HOME,
                fromWilayaId:      $fromWilayaId,
            );
        }

        return $result;
    }

    public function getCreateOrderValidationRules(): array
    {
        return [
            'order_id'            => ['required', 'string', 'max:100'],
            'first_name'          => ['required', 'string', 'max:100'],
            'last_name'           => ['required', 'string', 'max:100'],
            'phone'               => ['required', 'string'],
            'address'             => ['nullable', 'string', 'max:500'],
            'to_wilaya_id'        => ['nullable', 'integer', 'between:1,58'],
            'to_commune'          => ['nullable', 'string'],
            'product_description' => ['required', 'string', 'min:2', 'max:250'],
            'price'               => ['required', 'numeric', 'min:0', 'max:150000'],
            'delivery_type'       => ['required', 'integer', 'in:1,2'],
            'phone_alt'           => ['nullable', 'string'],
            'weight'              => ['nullable', 'numeric', 'min:0'],
            'length'              => ['nullable', 'numeric', 'min:0'],
            'width'               => ['nullable', 'numeric', 'min:0'],
            'height'              => ['nullable', 'numeric', 'min:0'],
            /**
             * Provide the district territory UUID in notes.
             * The city UUID is auto-resolved from toWilayaId via WILAYA_UUID_MAP.
             * To override the city UUID or provide both explicitly:
             *   "zr_city:{uuid}|zr_district:{uuid}|optional note"
             *   "zr_district:{uuid}|optional note"   ← preferred when toWilayaId is set
             */
            'notes'               => ['nullable', 'string'],
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
                . 'Pass it via CreateOrderData::$notes: '
                . '"zr_district:{uuid}|optional note". '
                . 'The city UUID is auto-resolved from toWilayaId when possible.',
            );
        }

        if ($cityTerritoryId === null) {
            throw new CourierException(
                'ZR Express NEW requires a city territory UUID. '
                . 'toWilayaId ' . ($data->toWilayaId ?? 'null') . ' is not in the wilaya map. '
                . 'Provide it explicitly: "zr_city:{uuid}|zr_district:{uuid}|optional note".',
            );
        }

        $payload = [
            'customer' => [
                'customerId' => $this->randomUuid(),
                'name'       => trim($data->firstName . ' ' . $data->lastName),
                'phone'      => [
                    'number1' => $data->phone,
                    'number2' => $data->phoneAlt,
                ],
            ],
            'deliveryAddress' => [
                'cityTerritoryId'     => $cityTerritoryId,
                'districtTerritoryId' => $districtTerritoryId,
                'street'              => $data->address ?: null,
            ],
            'orderedProducts' => [
                [
                    'productName' => $data->productDescription,
                    'unitPrice'   => $data->price,
                    'quantity'    => 1,
                    'stockType'   => 'none',
                ],
            ],
            'deliveryType' => $data->deliveryType === DeliveryType::STOP_DESK
                ? 'pickup-point'
                : 'home',
            'description' => $data->productDescription,
            'amount'      => $data->price,
            'externalId'  => $data->orderId,
        ];

        if ($data->deliveryType === DeliveryType::STOP_DESK && $data->stopDeskId !== null) {
            $payload['hubId'] = (string) $data->stopDeskId;
        }

        if ($data->weight !== null) {
            $payload['weight'] = ['weight' => $data->weight];
        }

        if ($data->length !== null || $data->width !== null || $data->height !== null) {
            $payload['orderedProducts'][0]['length'] = $data->length;
            $payload['orderedProducts'][0]['width']  = $data->width;
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
            // Send apiKey as Bearer token in addition to X-Api-Key
            ['Authorization' => "Bearer {$this->credentials->apiKey}"],
        );

        $labelFiles = $response['parcelLabelFiles'] ?? [];
        $failed     = $response['failedTrackingNumbers'] ?? [];

        if (!empty($failed) && in_array($trackingNumber, (array) $failed, strict: true)) {
            throw new CourierException(
                "ZR Express NEW could not generate a label for [{$trackingNumber}] — parcel not found or territory data missing.",
            );
        }

        if (empty($labelFiles)) {
            throw new CourierException(
                "ZR Express NEW returned no label for [{$trackingNumber}].",
            );
        }

        $file    = $labelFiles[0];
        $fileUrl = (string) ($file['fileUrl'] ?? '');

        if ($fileUrl === '') {
            throw new CourierException(
                "ZR Express NEW returned an empty label URL for [{$trackingNumber}].",
            );
        }

        return new LabelData(
            provider:       Provider::ZREXPRESS_NEW,
            trackingNumber: $trackingNumber,
            type:           LabelType::HTML_URL,
            url:            $fileUrl,
        );
    }

    // -------------------------------------------------------------------------
    // Public helpers (ZR Express NEW–specific)
    // -------------------------------------------------------------------------

    /**
     * Resolve a wilaya integer code to its ZR Express territory UUID.
     *
     * Useful when building the notes convention string programmatically:
     *
     * ```php
     * $cityUuid = $adapter->resolveCityUuid(16); // Alger
     * ```
     */
    public function resolveCityUuid(int $wilayaCode): ?string
    {
        return self::WILAYA_UUID_MAP[$wilayaCode] ?? null;
    }

    /**
     * Resolve a ZR Express territory UUID to its integer wilaya code.
     */
    public function resolveWilayaCode(string $territoryUuid): ?int
    {
        return self::WILAYA_CODE_MAP[$territoryUuid] ?? null;
    }

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
     *              [cityTerritoryId, districtTerritoryId, cleanNote]
     */
    private function parseTerritoryIds(?string $notes, ?int $toWilayaId = null): array
    {
        $cityId     = null;
        $districtId = null;
        $remaining  = [];

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
            $cityId = self::WILAYA_UUID_MAP[$toWilayaId] ?? null;
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
        $id    = $order->raw['id'] ?? null;

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
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0F | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3F | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function hydrateOrder(array $raw): OrderData
    {
        $stateName = (string) ($this->dig($raw, 'state', 'name') ?? '');
        $status    = $this->normalizeStatus($stateName);

        // cityTerritoryCode is the integer wilaya code (1-58)
        $wilayaCode = (int) ($this->dig($raw, 'deliveryAddress', 'cityTerritoryCode') ?? 0);

        // If code not present, try to resolve from the territory UUID
        if ($wilayaCode === 0) {
            $cityUuid   = (string) ($this->dig($raw, 'deliveryAddress', 'cityTerritoryId') ?? '');
            $wilayaCode = self::WILAYA_CODE_MAP[$cityUuid] ?? 0;
        }

        $commune      = (string) ($this->dig($raw, 'deliveryAddress', 'district') ?? '');
        $city         = (string) ($this->dig($raw, 'deliveryAddress', 'city') ?? '');
        $customerName = (string) ($this->dig($raw, 'customer', 'name') ?? '');
        $phone        = (string) ($this->dig($raw, 'customer', 'phone', 'number1') ?? '');

        return new OrderData(
            orderId:        (string) ($raw['externalId']      ?? ''),
            trackingNumber: (string) ($raw['trackingNumber']  ?? (string) ($raw['id'] ?? '')),
            provider:       Provider::ZREXPRESS_NEW,
            status:         $status,
            recipientName:  $customerName,
            phone:          $phone,
            address:        (string) ($this->dig($raw, 'deliveryAddress', 'street') ?? ''),
            toWilayaId:     $wilayaCode,
            toCommune:      $commune !== '' ? $commune : $city,
            price:          (float) ($raw['amount']           ?? 0),
            shippingFee:    isset($raw['deliveryPrice']) ? (float) $raw['deliveryPrice'] : null,
            rawStatus:      $stateName,
            notes:          $this->dig($raw, 'situation', 'name') !== null
                            ? (string) $this->dig($raw, 'situation', 'name')
                            : null,
            createdAt:      $this->parseDate($raw['createdAt']          ?? null),
            updatedAt:      $this->parseDate($raw['lastStateUpdateAt']  ?? null),
            raw:            $raw,
        );
    }
}
