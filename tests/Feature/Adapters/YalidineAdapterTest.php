<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Uften\Courier\Adapters\YalidineAdapter;
use Uften\Courier\Data\CreateOrderData;
use Uften\Courier\Data\Credentials\YalidineCredentials;
use Uften\Courier\Enums\LabelType;
use Uften\Courier\Enums\Provider;
use Uften\Courier\Enums\TrackingStatus;
use Uften\Courier\Exceptions\AuthenticationException;
use Uften\Courier\Exceptions\CourierException;
use Uften\Courier\Exceptions\OrderNotFoundException;

// -------------------------------------------------------------------------
// Helper: build an adapter backed by a Guzzle MockHandler
// -------------------------------------------------------------------------
function yalidineAdapter(array $responses): YalidineAdapter
{
    $mock = new MockHandler($responses);
    $handler = HandlerStack::create($mock);
    $client = new Client(['handler' => $handler, 'http_errors' => false]);

    return new YalidineAdapter(
        credentials: new YalidineCredentials('test-token', 'test-key'),
        httpClient: $client,
    );
}

// -------------------------------------------------------------------------
// Tests
// -------------------------------------------------------------------------
describe('YalidineAdapter', function (): void {

    it('belongs to the YALIDINE provider', function (): void {
        $adapter = yalidineAdapter([new Response(200, [], '{}')]);
        expect($adapter->provider())->toBe(Provider::YALIDINE);
    });

    it('testCredentials returns true on a 200 response', function (): void {
        $adapter = yalidineAdapter([
            new Response(200, [], json_encode(['data' => [], 'total' => 0])),
        ]);
        expect($adapter->testCredentials())->toBeTrue();
    });

    it('testCredentials returns false on a 401 response', function (): void {
        $adapter = yalidineAdapter([
            new Response(401, [], json_encode(['message' => 'Unauthorized'])),
        ]);
        expect($adapter->testCredentials())->toBeFalse();
    });

    it('createOrder returns a correctly shaped OrderData', function (): void {
        $payload = [
            'tracking' => 'ORD-001',
            'firstname' => 'Mohamed',
            'familyname' => 'Amrani',
            'contact_phone' => '0551234567',
            'address' => 'Rue des Roses',
            'to_wilaya_id' => 16,
            'to_commune_name' => 'Alger Centre',
            'price' => 12000,
            'last_status' => 'En préparation',
        ];

        $adapter = yalidineAdapter([new Response(200, [], json_encode($payload))]);

        $order = $adapter->createOrder(new CreateOrderData(
            orderId: 'ORD-001',
            firstName: 'Mohamed',
            lastName: 'Amrani',
            phone: '0551234567',
            address: 'Rue des Roses',
            toWilayaId: 16,
            toCommune: 'Alger Centre',
            productDescription: 'Smartphone',
            price: 12000.0,
        ));

        expect($order->orderId)->toBe('ORD-001')
            ->and($order->trackingNumber)->toBe('ORD-001')
            ->and($order->provider)->toBe(Provider::YALIDINE)
            ->and($order->status)->toBe(TrackingStatus::PENDING)
            ->and($order->rawStatus)->toBe('En préparation')
            ->and($order->recipientName)->toBe('Mohamed Amrani')
            ->and($order->toWilayaId)->toBe(16)
            ->and($order->price)->toBe(12000.0);
    });

    it('getOrder returns a correctly shaped OrderData', function (): void {
        $payload = [
            'tracking' => 'TRK-999',
            'firstname' => 'Sara',
            'familyname' => 'Meziani',
            'contact_phone' => '0661234567',
            'address' => 'Bab El Oued',
            'to_wilaya_id' => 16,
            'to_commune_name' => 'Alger',
            'price' => 5000,
            'last_status' => 'Livré',
            'price_delivery' => 400,
        ];

        $adapter = yalidineAdapter([new Response(200, [], json_encode($payload))]);
        $order = $adapter->getOrder('TRK-999');

        expect($order->trackingNumber)->toBe('TRK-999')
            ->and($order->status)->toBe(TrackingStatus::DELIVERED)
            ->and($order->isDelivered())->toBeTrue()
            ->and($order->shippingFee)->toBe(400.0);
    });

    it('getOrder throws OrderNotFoundException on empty response', function (): void {
        $adapter = yalidineAdapter([new Response(200, [], '{}')]);

        expect(fn () => $adapter->getOrder('GHOST-001'))
            ->toThrow(OrderNotFoundException::class);
    });

    it('getLabel returns a PDF URL label', function (): void {
        $adapter = yalidineAdapter([
            new Response(200, [], json_encode(['url' => 'https://cdn.yalidine.app/label/TRK-001.pdf'])),
        ]);

        $label = $adapter->getLabel('TRK-001');

        expect($label->type)->toBe(LabelType::PDF_URL)
            ->and($label->url)->toBe('https://cdn.yalidine.app/label/TRK-001.pdf')
            ->and($label->provider)->toBe(Provider::YALIDINE);
    });

    it('getLabel returns a Base64 PDF label', function (): void {
        $b64 = base64_encode('%PDF-1.4 fake');
        $adapter = yalidineAdapter([
            new Response(200, [], json_encode(['data' => $b64])),
        ]);

        $label = $adapter->getLabel('TRK-002');

        expect($label->type)->toBe(LabelType::PDF_BASE64)
            ->and($label->base64)->toBe($b64);
    });

    it('normalizes all mapped Yalidine statuses correctly', function (): void {
        $adapter = yalidineAdapter([]);

        $expectations = [
            'En préparation' => TrackingStatus::PENDING,
            'Enlevé' => TrackingStatus::PICKED_UP,
            'Transféré' => TrackingStatus::IN_TRANSIT,
            'En cours de livraison' => TrackingStatus::OUT_FOR_DELIVERY,
            'Livré' => TrackingStatus::DELIVERED,
            'Tentative échouée' => TrackingStatus::FAILED_DELIVERY,
            'En retour' => TrackingStatus::RETURNING,
            'Retourné' => TrackingStatus::RETURNED,
            'Annulé' => TrackingStatus::CANCELLED,
            'Stop desk' => TrackingStatus::READY_FOR_PICKUP,
            'Perdu' => TrackingStatus::EXCEPTION,
            'something-totally-weird' => TrackingStatus::UNKNOWN,
        ];

        foreach ($expectations as $raw => $expected) {
            expect($adapter->normalizeStatus($raw))
                ->toBe($expected, "Failed mapping raw status [{$raw}]");
        }
    });

    it('getRates throws CourierException when fromWilayaId is omitted', function (): void {
        $adapter = yalidineAdapter([]);

        expect(fn () => $adapter->getRates(toWilayaId: 16))
            ->toThrow(CourierException::class);
    });

    it('getRates returns an array of RateData objects', function (): void {
        $rates = [
            'data' => [
                ['wilaya_id' => 16, 'wilaya_name' => 'Alger',   'home_price' => 400, 'desk_price' => 200],
                ['wilaya_id' => 31, 'wilaya_name' => 'Oran',    'home_price' => 500, 'desk_price' => 250],
            ],
        ];

        $adapter = yalidineAdapter([new Response(200, [], json_encode($rates))]);
        $result = $adapter->getRates(fromWilayaId: 16);

        expect($result)->toHaveCount(2)
            ->and($result[0]->toWilayaId)->toBe(16)
            ->and($result[0]->homeDeliveryPrice)->toBe(400.0)
            ->and($result[0]->provider)->toBe(Provider::YALIDINE);
    });

    it('throws AuthenticationException on 401', function (): void {
        $adapter = yalidineAdapter([
            new Response(401, [], json_encode(['message' => 'Unauthorized'])),
        ]);

        expect(fn () => $adapter->getOrder('TRK-X'))
            ->toThrow(AuthenticationException::class);
    });

});
