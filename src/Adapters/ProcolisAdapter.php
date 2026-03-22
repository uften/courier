<?php

declare(strict_types=1);

namespace Uften\Courier\Adapters;

use GuzzleHttp\Client;
use Uften\Courier\Data\CreateOrderData;
use Uften\Courier\Data\Credentials\ProcolisCredentials;
use Uften\Courier\Data\LabelData;
use Uften\Courier\Data\OrderData;
use Uften\Courier\Data\RateData;
use Uften\Courier\Enums\DeliveryType;
use Uften\Courier\Enums\Provider;
use Uften\Courier\Enums\TrackingStatus;
use Uften\Courier\Exceptions\OrderNotFoundException;
use Uften\Courier\Exceptions\UnsupportedOperationException;

/**
 * Adapter for the Procolis API (also known as ZR Express).
 *
 * Auth: id + token in request body / query params.
 */
final class ProcolisAdapter extends AbstractAdapter
{
    private const array STATUS_MAP = [
        // Procolis returns French status strings
        'en attente' => TrackingStatus::PENDING,
        'préparation' => TrackingStatus::PENDING,
        'en préparation' => TrackingStatus::PENDING,
        'ramassé' => TrackingStatus::PICKED_UP,
        'enlevé' => TrackingStatus::PICKED_UP,
        'collecté' => TrackingStatus::PICKED_UP,
        'en transit' => TrackingStatus::IN_TRANSIT,
        'reçu en entrepôt' => TrackingStatus::IN_TRANSIT,
        'en cours de livraison' => TrackingStatus::OUT_FOR_DELIVERY,
        'sorti en livraison' => TrackingStatus::OUT_FOR_DELIVERY,
        'livré' => TrackingStatus::DELIVERED,
        'livraison effectuée' => TrackingStatus::DELIVERED,
        'tentative échouée' => TrackingStatus::FAILED_DELIVERY,
        'non livré' => TrackingStatus::FAILED_DELIVERY,
        'absent' => TrackingStatus::FAILED_DELIVERY,
        'refusé' => TrackingStatus::FAILED_DELIVERY,
        'retour en cours' => TrackingStatus::RETURNING,
        'en retour' => TrackingStatus::RETURNING,
        'retourné' => TrackingStatus::RETURNED,
        'retour livré' => TrackingStatus::RETURNED,
        'annulé' => TrackingStatus::CANCELLED,
        'perdu' => TrackingStatus::EXCEPTION,
        'endommagé' => TrackingStatus::EXCEPTION,
    ];

    public function __construct(
        private readonly ProcolisCredentials $credentials,
        private readonly Provider $resolvedProvider = Provider::PROCOLIS,
        ?Client $httpClient = null,
    ) {
        parent::__construct(
            baseUrl: Provider::PROCOLIS->baseUrl(),
            httpClient: $httpClient,
        );

        $this->providerEnum = $this->resolvedProvider;
    }

    public function normalizeStatus(string $rawStatus): TrackingStatus
    {
        return self::STATUS_MAP[mb_strtolower(trim($rawStatus))] ?? TrackingStatus::UNKNOWN;
    }

    public function testCredentials(): bool
    {
        try {
            $response = $this->get('livraisons', $this->authParams(['page' => '1']));

            return isset($response['livraisons']) || isset($response['data']) || is_array($response);
        } catch (\Throwable) {
            return false;
        }
    }

    public function getRates(?int $fromWilayaId = null, ?int $toWilayaId = null): array
    {
        $params = $this->authParams();

        if ($toWilayaId !== null) {
            $params['wilaya_id'] = (string) $toWilayaId;
        }

        $data = $this->get('tarifs', $params);

        $rows = $data['tarifs'] ?? $data['data'] ?? $data;

        if (! is_array($rows)) {
            return [];
        }

        return array_map(function (array $item) use ($fromWilayaId): RateData {
            return new RateData(
                provider: $this->providerEnum,
                toWilayaId: (int) ($item['wilaya_id'] ?? 0),
                toWilayaName: (string) ($item['wilaya_name'] ?? ''),
                homeDeliveryPrice: (float) ($item['tarif_domicile'] ?? $item['tarif'] ?? 0),
                stopDeskPrice: (float) ($item['tarif_bureau'] ?? $item['tarif'] ?? 0),
                deliveryType: DeliveryType::HOME,
                fromWilayaId: $fromWilayaId,
            );
        }, $rows);
    }

    public function getCreateOrderValidationRules(): array
    {
        return [
            'order_id' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string'],
            'address' => ['required', 'string'],
            'to_wilaya_id' => ['required', 'integer', 'between:1,58'],
            'to_commune' => ['required', 'string'],
            'product_description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'delivery_type' => ['required', 'integer', 'in:1,2'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function createOrder(CreateOrderData $data): OrderData
    {
        $payload = array_merge($this->authParams(), [
            'Tracking' => $data->orderId,
            'TypeLivraison' => $data->deliveryType->value,
            'TypeColis' => 0,
            'Confrimee' => 0,
            'Client' => $data->firstName.' '.$data->lastName,
            'MobileA' => $data->phone,
            'Adresse' => $data->address,
            'IDWilaya' => str_pad((string) $data->toWilayaId, 2, '0', STR_PAD_LEFT),
            'Commune' => $data->toCommune,
            'Total' => (string) $data->price,
            'TProduit' => $data->productDescription,
            'id_Externe' => $data->orderId,
        ]);

        if ($data->phoneAlt !== null) {
            $payload['MobileB'] = $data->phoneAlt;
        }

        if ($data->notes !== null) {
            $payload['Note'] = $data->notes;
        }

        $response = $this->post('livraisons', $payload);

        return $this->hydrateOrder(
            array_merge($response, ['_input' => $data->toArray()])
        );
    }

    public function getOrder(string $trackingNumber): OrderData
    {
        $response = $this->get("livraisons/{$trackingNumber}", $this->authParams());

        if (empty($response) || (isset($response['error']) && $response['error'])) {
            throw new OrderNotFoundException($trackingNumber);
        }

        return $this->hydrateOrder($response);
    }

    public function getLabel(string $trackingNumber): LabelData
    {
        throw new UnsupportedOperationException('getLabel', $this->providerEnum);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build the auth parameters that Procolis appends to every request.
     *
     * @param  array<string, string>  $extra
     * @return array<string, string>
     */
    private function authParams(array $extra = []): array
    {
        return array_merge([
            'id' => $this->credentials->id,
            'token' => $this->credentials->token,
        ], $extra);
    }

    private function hydrateOrder(array $raw): OrderData
    {
        $rawStatus = (string) ($raw['Statut'] ?? $raw['statut'] ?? $raw['status'] ?? '');
        $input = $raw['_input'] ?? [];

        return new OrderData(
            orderId: (string) ($raw['Tracking'] ?? $raw['id_Externe'] ?? $input['order_id'] ?? ''),
            trackingNumber: (string) ($raw['Tracking'] ?? ''),
            provider: $this->providerEnum,
            status: $this->normalizeStatus($rawStatus),
            recipientName: (string) ($raw['Client'] ?? ''),
            phone: (string) ($raw['MobileA'] ?? ''),
            address: (string) ($raw['Adresse'] ?? ''),
            toWilayaId: (int) ($raw['IDWilaya'] ?? 0),
            toCommune: (string) ($raw['Commune'] ?? ''),
            price: (float) ($raw['Total'] ?? 0),
            rawStatus: $rawStatus,
            notes: $raw['Note'] ?? null,
            createdAt: $this->parseDate($raw['Date'] ?? null),
            updatedAt: $this->parseDate($raw['DateModification'] ?? null),
            raw: $raw,
        );
    }
}
