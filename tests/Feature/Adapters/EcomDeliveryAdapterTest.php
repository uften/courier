<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Uften\Courier\Adapters\EcomDeliveryAdapter;
use Uften\Courier\Data\CreateOrderData;
use Uften\Courier\Data\Credentials\TokenCredentials;
use Uften\Courier\Enums\Provider;
use Uften\Courier\Enums\TrackingStatus;

function ecomDeliveryAdapter(array $responses): EcomDeliveryAdapter
{
    $client = new Client([
        'handler' => HandlerStack::create(new MockHandler($responses)),
        'http_errors' => false,
    ]);

    return new EcomDeliveryAdapter(
        credentials: new TokenCredentials('test-token'),
        httpClient: $client,
    );
}

describe('EcomDeliveryAdapter', function (): void {

    it('belongs to the ECOM_DELIVERY provider', function (): void {
        expect(ecomDeliveryAdapter([])->provider())->toBe(Provider::ECOM_DELIVERY);
    });

    it('testCredentials returns true on success', function (): void {
        $adapter = ecomDeliveryAdapter([new Response(200, [], json_encode(['data' => []]))]);
        expect($adapter->testCredentials())->toBeTrue();
    });

    it('testCredentials returns false on error', function (): void {
        $adapter = ecomDeliveryAdapter([new Response(401, [], '{}')]);
        expect($adapter->testCredentials())->toBeFalse();
    });

    it('createOrder creates order correctly', function (): void {
        $apiResponse = [
            'id' => 456,
            'tracking_number' => 'ECOM-456',
            'status' => 'pending',
        ];

        $adapter = ecomDeliveryAdapter([new Response(201, [], json_encode($apiResponse))]);
        $order = $adapter->createOrder(new CreateOrderData(
            orderId: 'ORD-004',
            firstName: 'Salim',
            lastName: 'Mebarki',
            phone: '0540000000',
            address: 'Setif',
            toWilayaId: 19,
            toCommune: 'Setif',
            productDescription: 'Gadgets',
            price: 4000,
        ));

        expect($order->trackingNumber)->toBe('ECOM-456')
            ->and($order->status)->toBe(TrackingStatus::PENDING);
    });
});
