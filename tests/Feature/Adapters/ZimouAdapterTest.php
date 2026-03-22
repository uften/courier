<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Uften\Courier\Adapters\ZimouAdapter;
use Uften\Courier\CourierManager;
use Uften\Courier\Data\CreateOrderData;
use Uften\Courier\Data\Credentials\TokenCredentials;
use Uften\Courier\Enums\DeliveryType;
use Uften\Courier\Enums\LabelType;
use Uften\Courier\Enums\Provider;
use Uften\Courier\Enums\TrackingStatus;
use Uften\Courier\Exceptions\CourierException;
use Uften\Courier\Exceptions\OrderNotFoundException;

// -------------------------------------------------------------------------
// Helper: build a ZimouAdapter backed by a Guzzle MockHandler
// -------------------------------------------------------------------------
function zimouAdapter(array $responses): ZimouAdapter
{
    $client = new Client([
        'handler' => HandlerStack::create(new MockHandler($responses)),
        'http_errors' => false,
    ]);

    return new ZimouAdapter(
        credentials: new TokenCredentials('test-bearer-token'),
        httpClient: $client,
    );
}

// -------------------------------------------------------------------------
// Sample fixtures
// -------------------------------------------------------------------------
function zimouPackageResource(array $overrides = []): array
{
    return array_merge([
        'id' => 1234,
        'order_id' => 'MY-ORD-001',
        'tracking_code' => 'ZM-ABC123',
        'delivery_company_tracking_code' => 'YALI-99999',
        'tracking_partner_company' => 'Yalidine',
        'name' => 'Smartphone',
        'status_id' => 1,
        'status_name' => 'EN PREPARATION',
        'client_first_name' => 'Mohamed',
        'client_last_name' => 'Amrani',
        'client_phone' => '0551234567',
        'client_phone2' => null,
        'address' => '12 Rue Didouche Mourad',
        'price' => 85000.0,
        'delivery_price' => 450.0,
        'observation' => null,
        'commune' => ['id' => 1, 'name' => 'Alger Centre', 'wilaya_id' => 16, 'wilaya' => ['id' => 16, 'name' => 'Alger']],
        'delivery_type' => 'Express',
        'delivery_type_id' => 2,
        'created_at' => '2024-11-01T10:00:00Z',
        'updated_at' => '2024-11-01T10:00:00Z',
    ], $overrides);
}

// =========================================================================
// Tests
// =========================================================================
describe('ZimouAdapter — identity', function (): void {

    it('belongs to Provider::ZIMOU', function (): void {
        expect(zimouAdapter([])->provider())->toBe(Provider::ZIMOU);
    });

    it('returns correct metadata', function (): void {
        $meta = zimouAdapter([])->metadata();
        expect($meta->title)->toBe('Zimou Express')
            ->and($meta->website)->toBe('https://zimou.express');
    });

});

// =========================================================================
describe('ZimouAdapter — testCredentials', function (): void {

    it('returns true on a 200 response', function (): void {
        $adapter = zimouAdapter([new Response(200, [], json_encode(['data' => ['id' => 1]]))]);
        expect($adapter->testCredentials())->toBeTrue();
    });

    it('returns false on 401', function (): void {
        $adapter = zimouAdapter([new Response(401, [], json_encode(['message' => 'Unauthenticated']))]);
        expect($adapter->testCredentials())->toBeFalse();
    });

});

// =========================================================================
describe('ZimouAdapter — createOrder', function (): void {

    it('creates an order and returns correct OrderData', function (): void {
        $responseBody = json_encode([
            'error' => 0,
            'order_id' => 'MY-ORD-001',
            'message' => 'Package created successfully',
            'data' => zimouPackageResource(),
        ]);

        $adapter = zimouAdapter([new Response(201, [], $responseBody)]);

        $order = $adapter->createOrder(new CreateOrderData(
            orderId: 'MY-ORD-001',
            firstName: 'Mohamed',
            lastName: 'Amrani',
            phone: '0551234567',
            address: '12 Rue Didouche Mourad',
            toWilayaId: 16,
            toCommune: 'Alger Centre',
            productDescription: 'Smartphone',
            price: 85000.0,
        ));

        expect($order->orderId)->toBe('MY-ORD-001')
            ->and($order->trackingNumber)->toBe('ZM-ABC123')
            ->and($order->provider)->toBe(Provider::ZIMOU)
            ->and($order->status)->toBe(TrackingStatus::PENDING)
            ->and($order->recipientName)->toBe('Mohamed Amrani')
            ->and($order->toWilayaId)->toBe(16)
            ->and($order->price)->toBe(85000.0)
            ->and($order->shippingFee)->toBe(450.0);
    });

    it('surfaces partner carrier info in notes', function (): void {
        $adapter = zimouAdapter([new Response(201, [], json_encode([
            'error' => 0, 'data' => zimouPackageResource(),
        ]))]);

        $order = $adapter->createOrder(new CreateOrderData(
            orderId: 'O1', firstName: 'A', lastName: 'B', phone: '0551234567',
            address: 'Addr', toWilayaId: 16, toCommune: 'Alger',
            productDescription: 'Item', price: 1000.0,
        ));

        expect($order->notes)->toContain('Via: Yalidine')
            ->and($order->notes)->toContain('Partner tracking: YALI-99999');
    });

    it('throws CourierException when Zimou returns error:1', function (): void {
        $adapter = zimouAdapter([new Response(201, [], json_encode([
            'error' => 1,
            'order_id' => '',
            'message' => 'Merci de mettre le nom du client',
        ]))]);

        expect(fn () => $adapter->createOrder(new CreateOrderData(
            orderId: 'O1', firstName: '', lastName: '', phone: '0551234567',
            address: 'A', toWilayaId: 16, toCommune: 'Alger',
            productDescription: 'I', price: 100.0,
        )))->toThrow(CourierException::class, 'Merci de mettre le nom du client');
    });

    it('maps DeliveryType::STOP_DESK to "Point relais" delivery type', function (): void {
        // We capture the actual request body to verify payload
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([new Response(201, [], json_encode([
            'error' => 0, 'data' => zimouPackageResource(),
        ]))]);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack, 'http_errors' => false]);
        $adapter = new ZimouAdapter(new TokenCredentials('t'), $client);

        $adapter->createOrder(new CreateOrderData(
            orderId: 'O2', firstName: 'A', lastName: 'B', phone: '0551234567',
            address: 'Addr', toWilayaId: 16, toCommune: 'Alger',
            productDescription: 'Item', price: 500.0,
            deliveryType: DeliveryType::STOP_DESK,
            stopDeskId: 99,
        ));

        $sentBody = json_decode((string) $container[0]['request']->getBody(), true);
        expect($sentBody['delivery_type'])->toBe('Point relais')
            ->and($sentBody['office_id'])->toBe(99);
    });

    it('uses "Flexible" delivery type when specified in notes', function (): void {
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([new Response(201, [], json_encode([
            'error' => 0, 'data' => zimouPackageResource(),
        ]))]);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack, 'http_errors' => false]);
        $adapter = new ZimouAdapter(new TokenCredentials('t'), $client);

        $adapter->createOrder(new CreateOrderData(
            orderId: 'O3', firstName: 'A', lastName: 'B', phone: '0551234567',
            address: 'Addr', toWilayaId: 16, toCommune: 'Alger',
            productDescription: 'Item', price: 500.0,
            notes: 'zimou_delivery_type:Flexible|Handle with care',
        ));

        $sentBody = json_decode((string) $container[0]['request']->getBody(), true);
        expect($sentBody['delivery_type'])->toBe('Flexible')
            ->and($sentBody['observation'])->toBe('Handle with care');
    });

    it('sets free_delivery to "true" when freeShipping is enabled', function (): void {
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([new Response(201, [], json_encode([
            'error' => 0, 'data' => zimouPackageResource(),
        ]))]);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack, 'http_errors' => false]);
        $adapter = new ZimouAdapter(new TokenCredentials('t'), $client);

        $adapter->createOrder(new CreateOrderData(
            orderId: 'O4', firstName: 'A', lastName: 'B', phone: '0551234567',
            address: 'Addr', toWilayaId: 16, toCommune: 'Alger',
            productDescription: 'Item', price: 0.0,
            freeShipping: true,
        ));

        $sentBody = json_decode((string) $container[0]['request']->getBody(), true);
        expect($sentBody['free_delivery'])->toBe('true');
    });

    it('includes volumetric dimensions when provided', function (): void {
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([new Response(201, [], json_encode([
            'error' => 0, 'data' => zimouPackageResource(),
        ]))]);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack, 'http_errors' => false]);
        $adapter = new ZimouAdapter(new TokenCredentials('t'), $client);

        $adapter->createOrder(new CreateOrderData(
            orderId: 'O5', firstName: 'A', lastName: 'B', phone: '0551234567',
            address: 'Addr', toWilayaId: 16, toCommune: 'Alger',
            productDescription: 'Item', price: 1000.0,
            length: 30.0, width: 20.0, height: 15.0,
        ));

        $sentBody = json_decode((string) $container[0]['request']->getBody(), true);
        expect($sentBody['volumetric'])->toEqual(['length' => 30.0, 'width' => 20.0, 'height' => 15.0]);
    });

});

// =========================================================================
describe('ZimouAdapter — getOrder', function (): void {

    it('fetches by integer ID using GET /v3/packages/{id}', function (): void {
        $adapter = zimouAdapter([
            new Response(200, [], json_encode(['data' => zimouPackageResource(['status_id' => 8, 'status_name' => 'LIVRÉ'])])),
        ]);

        $order = $adapter->getOrder('1234');

        expect($order->trackingNumber)->toBe('ZM-ABC123')
            ->and($order->status)->toBe(TrackingStatus::DELIVERED)
            ->and($order->isDelivered())->toBeTrue();
    });

    it('fetches by tracking code using GET /v3/packages/status', function (): void {
        $statusResponse = json_encode([
            'ZM-ABC123' => [
                'order_id' => 'MY-ORD-001',
                'status_id' => 7,
                'status_name' => 'SORTIE EN LIVRAISON',
                'client_first_name' => 'Mohamed',
                'client_last_name' => 'Amrani',
                'client_phone' => '0551234567',
                'address' => '12 Rue Didouche',
                'wilaya_id' => 16,
                'commune' => 'Alger Centre',
                'price' => 85000,
                'tracking_partner_company' => 'DHD',
            ],
        ]);

        $adapter = zimouAdapter([new Response(200, [], $statusResponse)]);
        $order = $adapter->getOrder('ZM-ABC123');

        expect($order->trackingNumber)->toBe('ZM-ABC123')
            ->and($order->status)->toBe(TrackingStatus::OUT_FOR_DELIVERY)
            ->and($order->notes)->toContain('Via: DHD');
    });

    it('throws OrderNotFoundException when status endpoint returns empty data', function (): void {
        $adapter = zimouAdapter([new Response(200, [], json_encode([]))]);
        expect(fn () => $adapter->getOrder('ZM-GHOST'))->toThrow(OrderNotFoundException::class);
    });

    it('throws OrderNotFoundException when package ID endpoint returns 404', function (): void {
        $adapter = zimouAdapter([new Response(404, [], json_encode(['message' => 'Not found']))]);
        expect(fn () => $adapter->getOrder('9999'))->toThrow(OrderNotFoundException::class);
    });

});

// =========================================================================
describe('ZimouAdapter — getLabel', function (): void {

    it('returns a PDF_BASE64 label from raw binary response', function (): void {
        $fakePdf = '%PDF-1.4 fake pdf content here';
        $adapter = zimouAdapter([new Response(200, [], $fakePdf)]);

        $label = $adapter->getLabel('ZM-ABC123');

        expect($label->type)->toBe(LabelType::PDF_BASE64)
            ->and($label->provider)->toBe(Provider::ZIMOU)
            ->and($label->trackingNumber)->toBe('ZM-ABC123')
            ->and($label->decodePdf())->toBe($fakePdf);
    });

    it('throws CourierException on empty label response', function (): void {
        $adapter = zimouAdapter([new Response(200, [], '')]);
        expect(fn () => $adapter->getLabel('ZM-NOLABEL'))
            ->toThrow(CourierException::class);
    });

});

// =========================================================================
describe('ZimouAdapter — normalizeStatus', function (): void {

    it('normalises by integer status_id string', function (): void {
        $adapter = zimouAdapter([]);
        expect($adapter->normalizeStatus('8'))->toBe(TrackingStatus::DELIVERED)
            ->and($adapter->normalizeStatus('1'))->toBe(TrackingStatus::PENDING)
            ->and($adapter->normalizeStatus('9'))->toBe(TrackingStatus::FAILED_DELIVERY)
            ->and($adapter->normalizeStatus('16'))->toBe(TrackingStatus::RETURNING)
            ->and($adapter->normalizeStatus('20'))->toBe(TrackingStatus::RETURNED)
            ->and($adapter->normalizeStatus('118'))->toBe(TrackingStatus::EXCEPTION)
            ->and($adapter->normalizeStatus('999'))->toBe(TrackingStatus::UNKNOWN);
    });

    it('normalises by status name string', function (): void {
        $adapter = zimouAdapter([]);
        expect($adapter->normalizeStatus('livré'))->toBe(TrackingStatus::DELIVERED)
            ->and($adapter->normalizeStatus('SORTIE EN LIVRAISON'))->toBe(TrackingStatus::OUT_FOR_DELIVERY)
            ->and($adapter->normalizeStatus('bloqué'))->toBe(TrackingStatus::EXCEPTION)
            ->and($adapter->normalizeStatus('perdu'))->toBe(TrackingStatus::EXCEPTION)
            ->and($adapter->normalizeStatus('something weird'))->toBe(TrackingStatus::UNKNOWN);
    });

    it('normaliseStatusById covers all 54 documented Zimou status IDs', function (): void {
        $adapter = zimouAdapter([]);

        $allIds = [
            1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20,
            21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40,
            41, 42, 43, 44, 45, 46, 83, 112, 113, 114, 115, 116, 118,
        ];

        foreach ($allIds as $id) {
            $result = $adapter->normalizeStatusById($id);
            expect($result)->not->toBe(TrackingStatus::UNKNOWN, "Status ID {$id} should be mapped");
        }
    });

    it('specific status IDs map to the correct canonical status', function (): void {
        $adapter = zimouAdapter([]);

        // Pending group
        foreach ([1, 2, 12, 26, 28, 33, 34, 35, 43, 44] as $id) {
            expect($adapter->normalizeStatusById($id))->toBe(TrackingStatus::PENDING, "ID {$id}");
        }

        // Picked up
        foreach ([3, 23, 39] as $id) {
            expect($adapter->normalizeStatusById($id))->toBe(TrackingStatus::PICKED_UP, "ID {$id}");
        }

        // In transit
        foreach ([4, 5, 6, 21, 24, 25, 40, 112, 114, 115] as $id) {
            expect($adapter->normalizeStatusById($id))->toBe(TrackingStatus::IN_TRANSIT, "ID {$id}");
        }

        // Out for delivery
        foreach ([7, 113] as $id) {
            expect($adapter->normalizeStatusById($id))->toBe(TrackingStatus::OUT_FOR_DELIVERY, "ID {$id}");
        }

        // Delivered
        expect($adapter->normalizeStatusById(8))->toBe(TrackingStatus::DELIVERED);

        // Failed delivery
        foreach ([9, 11, 13, 14, 15, 22] as $id) {
            expect($adapter->normalizeStatusById($id))->toBe(TrackingStatus::FAILED_DELIVERY, "ID {$id}");
        }

        // Returning
        foreach ([16, 17, 19, 116] as $id) {
            expect($adapter->normalizeStatusById($id))->toBe(TrackingStatus::RETURNING, "ID {$id}");
        }

        // Returned
        foreach ([20, 29, 37] as $id) {
            expect($adapter->normalizeStatusById($id))->toBe(TrackingStatus::RETURNED, "ID {$id}");
        }

        // Cancelled
        foreach ([30, 31, 41, 42, 45, 46] as $id) {
            expect($adapter->normalizeStatusById($id))->toBe(TrackingStatus::CANCELLED, "ID {$id}");
        }

        // Ready for pickup
        foreach ([18, 36] as $id) {
            expect($adapter->normalizeStatusById($id))->toBe(TrackingStatus::READY_FOR_PICKUP, "ID {$id}");
        }

        // Exception
        foreach ([10, 27, 32, 38, 83, 118] as $id) {
            expect($adapter->normalizeStatusById($id))->toBe(TrackingStatus::EXCEPTION, "ID {$id}");
        }
    });

});

// =========================================================================
describe('ZimouAdapter — getRates', function (): void {

    it('returns RateData from my/prices endpoint', function (): void {
        $response = json_encode([
            'data' => [
                ['wilaya_id' => 16, 'wilaya_name' => 'Alger', 'express_price' => 400, 'stopdesk_price' => 200],
                ['wilaya_id' => 9,  'wilaya_name' => 'Blida', 'express_price' => 350, 'stopdesk_price' => 150],
            ],
        ]);

        $adapter = zimouAdapter([new Response(200, [], $response)]);
        $rates = $adapter->getRates();

        expect($rates)->toHaveCount(2)
            ->and($rates[0]->toWilayaId)->toBe(16)
            ->and($rates[0]->homeDeliveryPrice)->toBe(400.0)
            ->and($rates[0]->stopDeskPrice)->toBe(200.0)
            ->and($rates[0]->provider)->toBe(Provider::ZIMOU);
    });

    it('returns empty array when prices endpoint returns no data', function (): void {
        $adapter = zimouAdapter([new Response(200, [], json_encode(['data' => []]))]);
        expect($adapter->getRates())->toBeEmpty();
    });

});

// =========================================================================
describe('ZimouAdapter — validation rules', function (): void {

    it('provides validation rules containing all required fields', function (): void {
        $rules = zimouAdapter([])->getCreateOrderValidationRules();
        expect($rules)->toHaveKey('order_id')
            ->and($rules)->toHaveKey('first_name')
            ->and($rules)->toHaveKey('phone')
            ->and($rules)->toHaveKey('to_wilaya_id')
            ->and($rules)->toHaveKey('price')
            ->and($rules)->toHaveKey('delivery_type');
    });

});

// =========================================================================
describe('ZimouAdapter — CourierManager integration', function (): void {

    it('resolves ZimouAdapter from CourierManager', function (): void {
        $adapter = app(CourierManager::class)->provider(Provider::ZIMOU);
        expect($adapter)->toBeInstanceOf(ZimouAdapter::class);
    });

    it('has correct provider enum identity after resolution', function (): void {
        $adapter = app(CourierManager::class)->provider(Provider::ZIMOU);
        expect($adapter->provider())->toBe(Provider::ZIMOU);
    });

});
