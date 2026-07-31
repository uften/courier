<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Uften\Courier\Adapters\NoestAdapter;
use Uften\Courier\Data\CreateOrderData;
use Uften\Courier\Data\Credentials\TokenCredentials;
use Uften\Courier\Enums\Provider;
use Uften\Courier\Enums\TrackingStatus;

function noestAdapter(array $responses): NoestAdapter
{
    $client = new Client([
        'handler' => HandlerStack::create(new MockHandler($responses)),
        'http_errors' => false,
    ]);

    return new NoestAdapter(
        credentials: new TokenCredentials('test-token'),
        httpClient: $client,
    );
}

describe('NoestAdapter', function (): void {

    it('belongs to the NOEST provider', function (): void {
        expect(noestAdapter([])->provider())->toBe(Provider::NOEST);
    });

    it('testCredentials returns true on success', function (): void {
        $adapter = noestAdapter([new Response(200, [], json_encode(['data' => []]))]);
        expect($adapter->testCredentials())->toBeTrue();
    });

    it('testCredentials returns false on error', function (): void {
        $adapter = noestAdapter([new Response(401, [], '{}')]);
        expect($adapter->testCredentials())->toBeFalse();
    });

    it('createOrder creates parcel correctly', function (): void {
        $apiResponse = [
            'id' => 123,
            'tracking' => 'NOEST-123',
            'status' => 'created',
        ];

        $adapter = noestAdapter([new Response(201, [], json_encode($apiResponse))]);
        $order = $adapter->createOrder(new CreateOrderData(
            orderId: 'ORD-003',
            firstName: 'Omar',
            lastName: 'Belkacem',
            phone: '0770000000',
            address: 'Constantine',
            toWilayaId: 25,
            toCommune: 'Constantine',
            productDescription: 'Books',
            price: 3000,
        ));

        expect($order->trackingNumber)->toBe('NOEST-123')
            ->and($order->status)->toBe(TrackingStatus::PENDING);
    });
});
