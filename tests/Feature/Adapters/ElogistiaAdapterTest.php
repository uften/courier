<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Uften\Courier\Adapters\ElogistiaAdapter;
use Uften\Courier\Data\CreateOrderData;
use Uften\Courier\Data\Credentials\TokenCredentials;
use Uften\Courier\Enums\Provider;
use Uften\Courier\Enums\TrackingStatus;

function elogistiaAdapter(array $responses): ElogistiaAdapter
{
    $client = new Client([
        'handler' => HandlerStack::create(new MockHandler($responses)),
        'http_errors' => false,
    ]);

    return new ElogistiaAdapter(
        credentials: new TokenCredentials('test-token'),
        httpClient: $client,
    );
}

describe('ElogistiaAdapter', function (): void {

    it('belongs to the ELOGISTIA provider', function (): void {
        expect(elogistiaAdapter([])->provider())->toBe(Provider::ELOGISTIA);
    });

    it('testCredentials returns true on success', function (): void {
        $adapter = elogistiaAdapter([new Response(200, [], json_encode(['data' => []]))]);
        expect($adapter->testCredentials())->toBeTrue();
    });

    it('testCredentials returns false on error', function (): void {
        $adapter = elogistiaAdapter([new Response(401, [], '{}')]);
        expect($adapter->testCredentials())->toBeFalse();
    });

    it('createOrder creates order correctly', function (): void {
        $apiResponse = [
            'id' => 'ELOG-12345',
            'tracking_code' => 'ELOG-12345',
            'status' => 'pending',
        ];

        $adapter = elogistiaAdapter([new Response(201, [], json_encode($apiResponse))]);
        $order = $adapter->createOrder(new CreateOrderData(
            orderId: 'ORD-001',
            firstName: 'Ahmed',
            lastName: 'Benali',
            phone: '0550000000',
            address: 'Algiers',
            toWilayaId: 16,
            toCommune: 'Alger Centre',
            productDescription: 'Clothes',
            price: 2500,
        ));

        expect($order->trackingNumber)->toBe('ELOG-12345')
            ->and($order->status)->toBe(TrackingStatus::PENDING);
    });

    it('getOrder returns OrderData', function (): void {
        $apiResponse = [
            'id' => 'ELOG-12345',
            'status' => 'livré',
        ];

        $adapter = elogistiaAdapter([new Response(200, [], json_encode($apiResponse))]);
        $order = $adapter->getOrder('ELOG-12345');

        expect($order->trackingNumber)->toBe('ELOG-12345')
            ->and($order->status)->toBe(TrackingStatus::DELIVERED);
    });
});
