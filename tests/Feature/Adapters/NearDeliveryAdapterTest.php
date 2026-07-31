<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Uften\Courier\Adapters\NearDeliveryAdapter;
use Uften\Courier\Data\CreateOrderData;
use Uften\Courier\Data\Credentials\TokenCredentials;
use Uften\Courier\Enums\Provider;
use Uften\Courier\Enums\TrackingStatus;

function nearDeliveryAdapter(array $responses): NearDeliveryAdapter
{
    $client = new Client([
        'handler' => HandlerStack::create(new MockHandler($responses)),
        'http_errors' => false,
    ]);

    return new NearDeliveryAdapter(
        credentials: new TokenCredentials('test-token'),
        httpClient: $client,
    );
}

describe('NearDeliveryAdapter', function (): void {

    it('belongs to the NEAR_DELIVERY provider', function (): void {
        expect(nearDeliveryAdapter([])->provider())->toBe(Provider::NEAR_DELIVERY);
    });

    it('testCredentials returns true on success', function (): void {
        $adapter = nearDeliveryAdapter([new Response(200, [], json_encode(['data' => []]))]);
        expect($adapter->testCredentials())->toBeTrue();
    });

    it('testCredentials returns false on error', function (): void {
        $adapter = nearDeliveryAdapter([new Response(401, [], '{}')]);
        expect($adapter->testCredentials())->toBeFalse();
    });

    it('createOrder creates shipment correctly', function (): void {
        $apiResponse = [
            'id' => 999,
            'tracking_number' => 'NEAR-999',
            'status' => 'confirmed',
        ];

        $adapter = nearDeliveryAdapter([new Response(201, [], json_encode($apiResponse))]);
        $order = $adapter->createOrder(new CreateOrderData(
            orderId: 'ORD-002',
            firstName: 'Karim',
            lastName: 'Hadj',
            phone: '0660000000',
            address: 'Oran',
            toWilayaId: 31,
            toCommune: 'Oran',
            productDescription: 'Electronics',
            price: 1500,
        ));

        expect($order->trackingNumber)->toBe('NEAR-999')
            ->and($order->status)->toBe(TrackingStatus::PENDING);
    });
});
