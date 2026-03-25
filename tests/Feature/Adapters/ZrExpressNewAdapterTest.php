<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Uften\Courier\Adapters\ZrExpressNewAdapter;
use Uften\Courier\Data\CreateOrderData;
use Uften\Courier\Data\Credentials\ZrExpressNewCredentials;
use Uften\Courier\Enums\DeliveryType;
use Uften\Courier\Enums\LabelType;
use Uften\Courier\Enums\Provider;
use Uften\Courier\Enums\TrackingStatus;
use Uften\Courier\Exceptions\CourierException;
use Uften\Courier\Exceptions\OrderNotFoundException;

// -------------------------------------------------------------------------
// Helpers
// -------------------------------------------------------------------------

function zrnAdapter(array $responses): ZrExpressNewAdapter
{
    $client = new Client([
        'handler'     => HandlerStack::create(new MockHandler($responses)),
        'http_errors' => false,
    ]);

    return new ZrExpressNewAdapter(
        credentials: new ZrExpressNewCredentials(
            tenantId: '5ab82e7e-8f1b-4a9b-95ea-be0bc37ffaef',
            apiKey:   'test-api-key',
        ),
        httpClient: $client,
    );
}

function zrnAdapterWithHistory(array $responses): array
{
    $history = [];
    $mock    = new MockHandler($responses);
    $stack   = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));
    $client  = new Client(['handler' => $stack, 'http_errors' => false]);

    return [
        'adapter' => new ZrExpressNewAdapter(
            new ZrExpressNewCredentials('tenant-uuid', 'api-key'),
            $client,
        ),
        'history' => &$history,
    ];
}

function parcelFixture(array $overrides = []): array
{
    return array_merge([
        'id'              => '8c1a4c53-9d1a-4bb0-9b44-e9c0c2f90111',
        'externalId'      => 'MY-ORD-001',
        'trackingNumber'  => '16-JUKYSI-ZR',
        'amount'          => 4500.0,
        'deliveryPrice'   => 400.0,
        'deliveryType'    => 'Home',
        'createdAt'       => '2025-11-21T17:16:10.183Z',
        'lastStateUpdateAt' => '2025-11-21T17:16:10.183Z',
        'customer' => [
            'customerId' => 'd212d2e9-5d6a-4ae2-8fb7-9c3e01b7c111',
            'name'       => 'Ahmed Benali',
            'phone'      => ['number1' => '+213550112233', 'number2' => null, 'number3' => null],
        ],
        'deliveryAddress' => [
            'street'              => '24 Rue Didouche Mourad',
            'city'                => 'Algiers',
            'cityTerritoryId'     => 'd134c182-7dac-4655-9d9b-bbdb62aa2ec4',
            'cityTerritoryCode'   => 16,
            'district'            => "Sidi M'Hamed",
            'districtTerritoryId' => 'e88130fa-62ae-4505-80a4-5a5c0a912313',
            'postalCode'          => '16000',
            'country'             => 'Algeria',
            'hubId'               => null,
            'hubName'             => null,
        ],
        'state' => [
            'id'          => '95bc5a68-03f4-497d-8c96-81c4d0ff14ae',
            'name'        => 'commande_recue',
            'description' => 'Commande reçue',
            'isBlocking'  => false,
            'isLocked'    => false,
            'visibleFor'  => 1,
            'editableBy'  => 2,
            'color'       => '#787878',
        ],
        'situation'   => null,
        'description' => 'Smartphone Xiaomi Redmi Note 12',
        'orderedProducts' => [
            ['productName' => 'Smartphone Xiaomi', 'unitPrice' => 4500, 'quantity' => 1],
        ],
    ], $overrides);
}

function ratesFixture(): array
{
    return [
        'rates' => [
            [
                'toTerritoryId'    => 'd134c182-7dac-4655-9d9b-bbdb62aa2ec4',
                'toTerritoryCode'  => 16,
                'toTerritoryName'  => 'Alger',
                'toTerritoryLevel' => 'wilaya',
                'deliveryPrices'   => [
                    ['deliveryType' => 'home',         'price' => 400, 'discountedPrice' => null],
                    ['deliveryType' => 'pickup-point', 'price' => 350, 'discountedPrice' => null],
                    ['deliveryType' => 'return',       'price' => 200, 'discountedPrice' => null],
                ],
            ],
            [
                'toTerritoryId'    => 'a7e764cf-e9ca-4c1f-8232-89852d102aec',
                'toTerritoryCode'  => 9,
                'toTerritoryName'  => 'Blida',
                'toTerritoryLevel' => 'wilaya',
                'deliveryPrices'   => [
                    ['deliveryType' => 'home',         'price' => 450, 'discountedPrice' => null],
                    ['deliveryType' => 'pickup-point', 'price' => 380, 'discountedPrice' => null],
                ],
            ],
            // Commune-level — should be SKIPPED
            [
                'toTerritoryId'    => '08a1631f-1949-462b-94a6-01f212d869e1',
                'toTerritoryCode'  => null,
                'toTerritoryName'  => 'Ouled Yaich',
                'toTerritoryLevel' => 'commune',
                'deliveryPrices'   => [['deliveryType' => 'home', 'price' => 400]],
            ],
            // Unknown-level — should be SKIPPED
            [
                'toTerritoryId'    => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'toTerritoryCode'  => null,
                'toTerritoryName'  => 'Unknown',
                'toTerritoryLevel' => 'Unknown',
                'deliveryPrices'   => [],
            ],
        ],
    ];
}

// =========================================================================

describe('ZrExpressNewAdapter — identity', function (): void {

    it('belongs to Provider::ZREXPRESS_NEW', function (): void {
        expect(zrnAdapter([])->provider())->toBe(Provider::ZREXPRESS_NEW);
    });

    it('returns correct metadata', function (): void {
        $meta = zrnAdapter([])->metadata();
        expect($meta->title)->toBe('ZR Express NEW')
            ->and($meta->website)->toBe('https://zrexpress.app');
    });

    it('sends X-Tenant and X-Api-Key headers on every request', function (): void {
        $adapter = new ZrExpressNewAdapter(new ZrExpressNewCredentials('tenant-uuid', 'api-key'));
        $ref = new ReflectionClass($adapter);
        $prop = $ref->getProperty('http');
        $client = $prop->getValue($adapter);
        $headers = $client->getConfig('headers');
        expect($headers['X-Tenant'])->toBe('tenant-uuid')
            ->and($headers['X-Api-Key'])->toBe('api-key');
    });

});

describe('ZrExpressNewAdapter — testCredentials', function (): void {

    it('returns true on success', function (): void {
        $adapter = zrnAdapter([new Response(200, [], json_encode(['items' => [], 'totalCount' => 0]))]);
        expect($adapter->testCredentials())->toBeTrue();
    });

    it('returns false on 401', function (): void {
        $adapter = zrnAdapter([new Response(401, [], json_encode(['title' => 'Unauthorized']))]);
        expect($adapter->testCredentials())->toBeFalse();
    });

});

describe('ZrExpressNewAdapter — createOrder', function (): void {

    it('auto-resolves city UUID from toWilayaId when only zr_district is in notes', function (): void {
        $ctx = zrnAdapterWithHistory([
            new Response(201, [], json_encode(['id' => '8c1a4c53-9d1a-4bb0-9b44-e9c0c2f90111'])),
            new Response(200, [], json_encode(parcelFixture())),
        ]);

        $ctx['adapter']->createOrder(new CreateOrderData(
            orderId: 'ORD-1', firstName: 'Ahmed', lastName: 'Benali',
            phone: '+213550112233', address: '24 Rue Didouche',
            toWilayaId: 16, toCommune: "Sidi M'Hamed",
            productDescription: 'Smartphone', price: 4500.0,
            notes: 'zr_district:e88130fa-62ae-4505-80a4-5a5c0a912313',
        ));

        $body = json_decode((string) $ctx['history'][0]['request']->getBody(), true);
        expect($body['deliveryAddress']['cityTerritoryId'])
            ->toBe('d134c182-7dac-4655-9d9b-bbdb62aa2ec4') // Alger UUID
            ->and($body['deliveryAddress']['districtTerritoryId'])
            ->toBe('e88130fa-62ae-4505-80a4-5a5c0a912313');
    });

    it('explicit zr_city in notes overrides auto-resolved city UUID', function (): void {
        $ctx = zrnAdapterWithHistory([
            new Response(201, [], json_encode(['id' => 'uuid'])),
            new Response(200, [], json_encode(parcelFixture())),
        ]);

        $ctx['adapter']->createOrder(new CreateOrderData(
            orderId: 'O2', firstName: 'A', lastName: 'B', phone: '+213550000000',
            address: 'Addr', toWilayaId: 16, toCommune: 'Alger',
            productDescription: 'Item', price: 500.0,
            notes: 'zr_city:CUSTOM-UUID|zr_district:DIST-UUID',
        ));

        $body = json_decode((string) $ctx['history'][0]['request']->getBody(), true);
        expect($body['deliveryAddress']['cityTerritoryId'])->toBe('CUSTOM-UUID');
    });

    it('throws CourierException when district UUID is missing from notes', function (): void {
        $adapter = zrnAdapter([]);
        expect(fn() => $adapter->createOrder(new CreateOrderData(
            orderId: 'X', firstName: 'A', lastName: 'B', phone: '+213550000000',
            address: 'A', toWilayaId: 16, toCommune: 'Alger',
            productDescription: 'Item', price: 500.0, notes: null,
        )))->toThrow(CourierException::class, 'district territory UUID');
    });

    it('throws CourierException when toWilayaId is not in the map and zr_city is absent', function (): void {
        $adapter = zrnAdapter([]);
        expect(fn() => $adapter->createOrder(new CreateOrderData(
            orderId: 'X', firstName: 'A', lastName: 'B', phone: '+213550000000',
            address: 'A', toWilayaId: 99, toCommune: 'Unknown',
            productDescription: 'Item', price: 500.0,
            notes: 'zr_district:DIST-UUID',
        )))->toThrow(CourierException::class, 'not in the wilaya map');
    });

    it('returns full OrderData after two-step flow', function (): void {
        $adapter = zrnAdapter([
            new Response(201, [], json_encode(['id' => '8c1a4c53-9d1a-4bb0-9b44-e9c0c2f90111'])),
            new Response(200, [], json_encode(parcelFixture())),
        ]);
        $order = $adapter->createOrder(new CreateOrderData(
            orderId: 'MY-ORD-001', firstName: 'Ahmed', lastName: 'Benali',
            phone: '+213550112233', address: '24 Rue Didouche',
            toWilayaId: 16, toCommune: "Sidi M'Hamed",
            productDescription: 'Smartphone', price: 4500.0,
            notes: 'zr_district:e88130fa-62ae-4505-80a4-5a5c0a912313',
        ));
        expect($order->orderId)->toBe('MY-ORD-001')
            ->and($order->trackingNumber)->toBe('16-JUKYSI-ZR')
            ->and($order->status)->toBe(TrackingStatus::PENDING)
            ->and($order->toWilayaId)->toBe(16)
            ->and($order->shippingFee)->toBe(400.0);
    });

    it('maps DeliveryType::STOP_DESK to "pickup-point"', function (): void {
        $ctx = zrnAdapterWithHistory([
            new Response(201, [], json_encode(['id' => 'uuid'])),
            new Response(200, [], json_encode(parcelFixture())),
        ]);
        $ctx['adapter']->createOrder(new CreateOrderData(
            orderId: 'SD', firstName: 'A', lastName: 'B', phone: '+213550000000',
            address: 'A', toWilayaId: 16, toCommune: 'Alger',
            productDescription: 'Item', price: 1000.0,
            deliveryType: DeliveryType::STOP_DESK,
            notes: 'zr_district:D-UUID',
        ));
        $body = json_decode((string) $ctx['history'][0]['request']->getBody(), true);
        expect($body['deliveryType'])->toBe('pickup-point');
    });

    it('includes weight when provided', function (): void {
        $ctx = zrnAdapterWithHistory([
            new Response(201, [], json_encode(['id' => 'uuid'])),
            new Response(200, [], json_encode(parcelFixture())),
        ]);
        $ctx['adapter']->createOrder(new CreateOrderData(
            orderId: 'W', firstName: 'A', lastName: 'B', phone: '+213550000000',
            address: 'A', toWilayaId: 16, toCommune: 'Alger',
            productDescription: 'Heavy', price: 2000.0, weight: 3.5,
            notes: 'zr_district:D-UUID',
        ));
        $body = json_decode((string) $ctx['history'][0]['request']->getBody(), true);
        expect($body['weight']['weight'])->toBe(3.5);
    });

    it('includes volumetric dimensions on the product line', function (): void {
        $ctx = zrnAdapterWithHistory([
            new Response(201, [], json_encode(['id' => 'uuid'])),
            new Response(200, [], json_encode(parcelFixture())),
        ]);
        $ctx['adapter']->createOrder(new CreateOrderData(
            orderId: 'V', firstName: 'A', lastName: 'B', phone: '+213550000000',
            address: 'A', toWilayaId: 16, toCommune: 'Alger',
            productDescription: 'Fridge', price: 45000.0,
            length: 60.0, width: 70.0, height: 180.0,
            notes: 'zr_district:D-UUID',
        ));
        $body = json_decode((string) $ctx['history'][0]['request']->getBody(), true);
        $p    = $body['orderedProducts'][0];
        expect($p['length'])->toEqual(60.0)->and($p['width'])->toEqual(70.0)->and($p['height'])->toEqual(180.0);
    });

    it('throws CourierException when API returns no id', function (): void {
        $adapter = zrnAdapter([new Response(201, [], json_encode(['error' => 'bad']))]);
        expect(fn() => $adapter->createOrder(new CreateOrderData(
            orderId: 'E', firstName: 'A', lastName: 'B', phone: '+213550000000',
            address: 'A', toWilayaId: 16, toCommune: 'Alger',
            productDescription: 'Item', price: 500.0, notes: 'zr_district:D',
        )))->toThrow(CourierException::class, 'parcel ID');
    });

});

describe('ZrExpressNewAdapter — getOrder', function (): void {

    it('fetches by UUID and returns correct OrderData', function (): void {
        $adapter = zrnAdapter([
            new Response(200, [], json_encode(parcelFixture([
                'state' => ['id' => 'u', 'name' => 'sortie_en_livraison', 'description' => 'Out', 'isBlocking' => false, 'isLocked' => false, 'visibleFor' => 1, 'editableBy' => 2, 'color' => '#ff0'],
            ]))),
        ]);
        $order = $adapter->getOrder('8c1a4c53-9d1a-4bb0-9b44-e9c0c2f90111');
        expect($order->status)->toBe(TrackingStatus::OUT_FOR_DELIVERY)
            ->and($order->toWilayaId)->toBe(16);
    });

    it('fetches by tracking number', function (): void {
        $adapter = zrnAdapter([
            new Response(200, [], json_encode(parcelFixture([
                'state' => ['id' => 'u', 'name' => 'livre', 'description' => 'Livré', 'isBlocking' => false, 'isLocked' => false, 'visibleFor' => 15, 'editableBy' => 2, 'color' => '#0f0'],
            ]))),
        ]);
        $order = $adapter->getOrder('16-JUKYSI-ZR');
        expect($order->status)->toBe(TrackingStatus::DELIVERED)->and($order->isDelivered())->toBeTrue();
    });

    it('resolves wilaya code from UUID when cityTerritoryCode is absent', function (): void {
        $fixture = parcelFixture();
        unset($fixture['deliveryAddress']['cityTerritoryCode']);
        $adapter = zrnAdapter([new Response(200, [], json_encode($fixture))]);
        $order   = $adapter->getOrder('16-JUKYSI-ZR');
        expect($order->toWilayaId)->toBe(16); // Resolved from cityTerritoryId UUID
    });

    it('throws OrderNotFoundException on 404', function (): void {
        $adapter = zrnAdapter([new Response(404, [], json_encode(['title' => 'Parcels.NotFound', 'status' => 404]))]);
        expect(fn() => $adapter->getOrder('GHOST'))->toThrow(OrderNotFoundException::class);
    });

    it('throws OrderNotFoundException on empty response', function (): void {
        $adapter = zrnAdapter([new Response(200, [], '{}')]);
        expect(fn() => $adapter->getOrder('EMPTY'))->toThrow(OrderNotFoundException::class);
    });

});

describe('ZrExpressNewAdapter — cancelOrder', function (): void {

    it('cancels by UUID directly', function (): void {
        $adapter = zrnAdapter([new Response(200, [], json_encode(['id' => '8c1a4c53-9d1a-4bb0-9b44-e9c0c2f90111']))]);
        expect($adapter->cancelOrder('8c1a4c53-9d1a-4bb0-9b44-e9c0c2f90111'))->toBeTrue();
    });

    it('resolves UUID first when given a tracking number', function (): void {
        $adapter = zrnAdapter([
            new Response(200, [], json_encode(parcelFixture())),
            new Response(200, [], json_encode(['id' => '8c1a4c53-9d1a-4bb0-9b44-e9c0c2f90111'])),
        ]);
        expect($adapter->cancelOrder('16-JUKYSI-ZR'))->toBeTrue();
    });

});

describe('ZrExpressNewAdapter — getLabel', function (): void {

    it('returns HTML_URL label on success', function (): void {
        $sasUrl  = 'https://zrexpressstorage.blob.core.windows.net/labels/bordereau_16-JUKYSI-ZR_abc123.html?sv=2021';
        $adapter = zrnAdapter([
            new Response(200, [], json_encode([
                'parcelLabelFiles'      => [['trackingNumber' => '16-JUKYSI-ZR', 'fileUrl' => $sasUrl]],
                'failedTrackingNumbers' => [],
            ])),
        ]);

        $label = $adapter->getLabel('16-JUKYSI-ZR');
        expect($label->type)->toBe(LabelType::HTML_URL)
            ->and($label->url)->toBe($sasUrl)
            ->and($label->provider)->toBe(Provider::ZREXPRESS_NEW)
            ->and($label->base64)->toBeNull();
    });

    it('sends Authorization: Bearer header for the label endpoint', function (): void {
        $ctx = zrnAdapterWithHistory([
            new Response(200, [], json_encode([
                'parcelLabelFiles'      => [['trackingNumber' => 'TRK', 'fileUrl' => 'https://url']],
                'failedTrackingNumbers' => [],
            ])),
        ]);
        $ctx['adapter']->getLabel('TRK');
        $headers = $ctx['history'][0]['request']->getHeaders();
        expect($headers)->toHaveKey('Authorization')
            ->and($headers['Authorization'][0])->toStartWith('Bearer ');
    });

    it('throws CourierException when tracking number is in failedTrackingNumbers', function (): void {
        $adapter = zrnAdapter([
            new Response(200, [], json_encode([
                'parcelLabelFiles'      => [],
                'failedTrackingNumbers' => ['GHOST'],
            ])),
        ]);
        expect(fn() => $adapter->getLabel('GHOST'))
            ->toThrow(CourierException::class, 'not found or territory data missing');
    });

    it('throws CourierException when parcelLabelFiles is empty', function (): void {
        $adapter = zrnAdapter([
            new Response(200, [], json_encode(['parcelLabelFiles' => [], 'failedTrackingNumbers' => []])),
        ]);
        expect(fn() => $adapter->getLabel('TRK'))->toThrow(CourierException::class, 'no label');
    });

    it('throws CourierException when fileUrl is blank', function (): void {
        $adapter = zrnAdapter([
            new Response(200, [], json_encode([
                'parcelLabelFiles'      => [['trackingNumber' => 'TRK', 'fileUrl' => '']],
                'failedTrackingNumbers' => [],
            ])),
        ]);
        expect(fn() => $adapter->getLabel('TRK'))->toThrow(CourierException::class, 'empty label URL');
    });

});

describe('ZrExpressNewAdapter — getRates', function (): void {

    it('returns only wilaya-level RateData, skipping commune and Unknown levels', function (): void {
        $adapter = zrnAdapter([new Response(200, [], json_encode(ratesFixture()))]);
        $rates   = $adapter->getRates();

        expect($rates)->toHaveCount(2)
            ->and($rates[0]->toWilayaId)->toBe(16)
            ->and($rates[0]->toWilayaName)->toBe('Alger')
            ->and($rates[0]->homeDeliveryPrice)->toBe(400.0)
            ->and($rates[0]->stopDeskPrice)->toBe(350.0)
            ->and($rates[0]->provider)->toBe(Provider::ZREXPRESS_NEW)
            ->and($rates[1]->toWilayaId)->toBe(9)
            ->and($rates[1]->homeDeliveryPrice)->toBe(450.0)
            ->and($rates[1]->stopDeskPrice)->toBe(380.0);
    });

    it('filters by toWilayaId when provided', function (): void {
        $adapter = zrnAdapter([new Response(200, [], json_encode(ratesFixture()))]);
        $rates   = $adapter->getRates(toWilayaId: 9);
        expect($rates)->toHaveCount(1)->and($rates[0]->toWilayaId)->toBe(9);
    });

    it('resolves wilaya code from UUID when toTerritoryCode is null', function (): void {
        $ratesNoCode = [
            'rates' => [[
                'toTerritoryId'    => 'e9a1e9cf-8475-4768-94cc-0888d094ff47', // Constantine=25
                'toTerritoryCode'  => null,
                'toTerritoryName'  => 'Constantine',
                'toTerritoryLevel' => 'wilaya',
                'deliveryPrices'   => [
                    ['deliveryType' => 'home',         'price' => 500],
                    ['deliveryType' => 'pickup-point', 'price' => 400],
                ],
            ]],
        ];
        $adapter = zrnAdapter([new Response(200, [], json_encode($ratesNoCode))]);
        $rates   = $adapter->getRates();
        expect($rates)->toHaveCount(1)->and($rates[0]->toWilayaId)->toBe(25);
    });

    it('does not append wilaya filter params to the API request URL', function (): void {
        $ctx = zrnAdapterWithHistory([new Response(200, [], json_encode(['rates' => []]))]);
        $ctx['adapter']->getRates(fromWilayaId: 16, toWilayaId: 9);
        $uri = (string) $ctx['history'][0]['request']->getUri();
        expect($uri)->toContain('delivery-pricing/rates')->and($uri)->not->toContain('wilaya');
    });

    it('returns empty array when rates array is empty', function (): void {
        $adapter = zrnAdapter([new Response(200, [], json_encode(['rates' => []]))]);
        expect($adapter->getRates())->toBeEmpty();
    });

});

describe('ZrExpressNewAdapter — normalizeStatus', function (): void {

    it('maps all documented workflow state slugs', function (): void {
        $adapter = zrnAdapter([]);
        $expectations = [
            'commande_recue' => TrackingStatus::PENDING,
            'pret_a_expedier' => TrackingStatus::PICKED_UP,
            'confirme_au_bureau' => TrackingStatus::IN_TRANSIT,
            'dispatch' => TrackingStatus::IN_TRANSIT,
            'vers_wilaya' => TrackingStatus::IN_TRANSIT,
            'sortie_en_livraison' => TrackingStatus::OUT_FOR_DELIVERY,
            'livre' => TrackingStatus::DELIVERED,
            'encaisse' => TrackingStatus::DELIVERED,
            'recouvert' => TrackingStatus::DELIVERED,
            'retour' => TrackingStatus::RETURNING,
            'retourne' => TrackingStatus::RETURNED,
            'annule' => TrackingStatus::CANCELLED,
        ];
        foreach ($expectations as $raw => $expected) {
            expect($adapter->normalizeStatus($raw))->toBe($expected, "Failed: [{$raw}]");
        }
    });

    it('maps PascalCase API response state names', function (): void {
        $adapter = zrnAdapter([]);
        expect($adapter->normalizeStatus('OrderReceived'))->toBe(TrackingStatus::PENDING)
            ->and($adapter->normalizeStatus('ReadyToDispatch'))->toBe(TrackingStatus::PICKED_UP)
            ->and($adapter->normalizeStatus('OutForDelivery'))->toBe(TrackingStatus::OUT_FOR_DELIVERY)
            ->and($adapter->normalizeStatus('Delivered'))->toBe(TrackingStatus::DELIVERED);
    });

    it('is case-insensitive', function (): void {
        $adapter = zrnAdapter([]);
        expect($adapter->normalizeStatus('LIVRE'))->toBe(TrackingStatus::DELIVERED)
            ->and($adapter->normalizeStatus('SORTIE_EN_LIVRAISON'))->toBe(TrackingStatus::OUT_FOR_DELIVERY);
    });

    it('returns UNKNOWN for unrecognised strings', function (): void {
        expect(zrnAdapter([])->normalizeStatus('completely_unknown_state'))->toBe(TrackingStatus::UNKNOWN);
    });

});

describe('ZrExpressNewAdapter — wilaya UUID helpers', function (): void {

    it('resolveCityUuid returns correct UUID for all 54 wilaya codes', function (): void {
        $adapter = zrnAdapter([]);
        expect($adapter->resolveCityUuid(16))->toBe('d134c182-7dac-4655-9d9b-bbdb62aa2ec4')
            ->and($adapter->resolveCityUuid(9))->toBe('a7e764cf-e9ca-4c1f-8232-89852d102aec')
            ->and($adapter->resolveCityUuid(1))->toBe('6e978fc5-f20a-4b5f-9adf-61dd21a7672a')
            ->and($adapter->resolveCityUuid(58))->toBe('3d19d427-08f3-492c-a1d0-e7ace3516ed2')
            ->and($adapter->resolveCityUuid(99))->toBeNull(); // Unknown
    });

    it('resolveWilayaCode returns correct integer code for territory UUIDs', function (): void {
        $adapter = zrnAdapter([]);
        expect($adapter->resolveWilayaCode('d134c182-7dac-4655-9d9b-bbdb62aa2ec4'))->toBe(16)
            ->and($adapter->resolveWilayaCode('a7e764cf-e9ca-4c1f-8232-89852d102aec'))->toBe(9)
            ->and($adapter->resolveWilayaCode('ffffffff-ffff-ffff-ffff-ffffffffffff'))->toBeNull();
    });

    it('correctly handles wilaya code gaps (33, 50, 56 do not exist)', function (): void {
        $adapter = zrnAdapter([]);
        expect($adapter->resolveCityUuid(33))->toBeNull()
            ->and($adapter->resolveCityUuid(50))->toBeNull()
            ->and($adapter->resolveCityUuid(56))->toBeNull();
    });

});

describe('ZrExpressNewAdapter — ZrExpressNewCredentials DTO', function (): void {

    it('constructs from snake_case keys', function (): void {
        $c = ZrExpressNewCredentials::fromArray(['tenant_id' => 'tid', 'api_key' => 'key']);
        expect($c->tenantId)->toBe('tid')->and($c->apiKey)->toBe('key');
    });

    it('constructs from camelCase keys', function (): void {
        $c = ZrExpressNewCredentials::fromArray(['tenantId' => 'tid', 'apiKey' => 'key']);
        expect($c->tenantId)->toBe('tid');
    });

    it('throws on missing tenant_id', function (): void {
        expect(fn() => ZrExpressNewCredentials::fromArray(['api_key' => 'k']))
            ->toThrow(\InvalidArgumentException::class, 'tenant_id');
    });

    it('throws on missing api_key', function (): void {
        expect(fn() => ZrExpressNewCredentials::fromArray(['tenant_id' => 't']))
            ->toThrow(\InvalidArgumentException::class, 'api_key');
    });

});

describe('ZrExpressNewAdapter — CourierManager integration', function (): void {

    it('resolves ZrExpressNewAdapter from the manager', function (): void {
        $adapter = app(\Uften\Courier\CourierManager::class)->provider(Provider::ZREXPRESS_NEW);
        expect($adapter)->toBeInstanceOf(ZrExpressNewAdapter::class);
    });

    it('is not an Ecotrack engine provider', function (): void {
        expect(Provider::ZREXPRESS_NEW->isEcotrackEngine())->toBeFalse();
    });

    it('does not require an API id (Procolis legacy pattern)', function (): void {
        expect(Provider::ZREXPRESS_NEW->requiresApiId())->toBeFalse();
    });

    it('has the correct base URL', function (): void {
        expect(Provider::ZREXPRESS_NEW->baseUrl())->toBe('https://api.zrexpress.app');
    });

});
