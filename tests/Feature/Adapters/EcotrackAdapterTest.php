<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Uften\Courier\Adapters\EcotrackAdapter;
use Uften\Courier\Data\CreateOrderData;
use Uften\Courier\Data\Credentials\TokenCredentials;
use Uften\Courier\Enums\Provider;
use Uften\Courier\Enums\TrackingStatus;
use Uften\Courier\Exceptions\OrderNotFoundException;

function ecotrackAdapter(array $responses): EcotrackAdapter
{
    $client = new Client([
        'handler' => HandlerStack::create(new MockHandler($responses)),
        'http_errors' => false,
    ]);

    return new EcotrackAdapter(
        credentials: new TokenCredentials('test-token'),
        httpClient: $client,
    );
}

describe('EcotrackAdapter', function (): void {

    it('belongs to the ECOTRACK provider', function (): void {
        expect(ecotrackAdapter([])->provider())->toBe(Provider::ECOTRACK);
    });

    it('testCredentials returns true on success', function (): void {
        $adapter = ecotrackAdapter([new Response(200, [], json_encode(['data' => []]))]);
        expect($adapter->testCredentials())->toBeTrue();
    });

    it('testCredentials returns false on 401', function (): void {
        $adapter = ecotrackAdapter([new Response(401, [], '{}')]);
        expect($adapter->testCredentials())->toBeFalse();
    });

    it('createOrder returns correctly shaped OrderData', function (): void {
        $apiResponse = [
            'id' => 'ECO-001',
            'reference' => 'ORD-200',
            'tracking' => 'ECO-TRK-001',
            'firstname' => 'Ali',
            'lastname' => 'Khelifi',
            'phone' => '0771234567',
            'address' => 'Tizi Centre',
            'wilaya_id' => 15,
            'commune' => 'Tizi Ouzou',
            'cod' => 3500,
            'status' => 'created',
            'created_at' => '2024-10-15T09:30:00Z',
            'updated_at' => '2024-10-15T09:30:00Z',
        ];

        $adapter = ecotrackAdapter([new Response(200, [], json_encode($apiResponse))]);

        $order = $adapter->createOrder(new CreateOrderData(
            orderId: 'ORD-200',
            firstName: 'Ali',
            lastName: 'Khelifi',
            phone: '0771234567',
            address: 'Tizi Centre',
            toWilayaId: 15,
            toCommune: 'Tizi Ouzou',
            productDescription: 'Shoes',
            price: 3500.0,
        ));

        expect($order->provider)->toBe(Provider::ECOTRACK)
            ->and($order->trackingNumber)->toBe('ECO-TRK-001')
            ->and($order->status)->toBe(TrackingStatus::PENDING)
            ->and($order->recipientName)->toBe('Ali Khelifi')
            ->and($order->toWilayaId)->toBe(15)
            ->and($order->price)->toBe(3500.0)
            ->and($order->createdAt)->not->toBeNull();
    });

    it('getOrder throws OrderNotFoundException when error key present', function (): void {
        $adapter = ecotrackAdapter([
            new Response(200, [], json_encode(['error' => 'not found'])),
        ]);
        expect(fn () => $adapter->getOrder('GHOST'))->toThrow(OrderNotFoundException::class);
    });

    it('getLabel returns base64 label', function (): void {
        $b64 = base64_encode('%PDF-1.4 fake label');
        $adapter = ecotrackAdapter([
            new Response(200, [], json_encode(['label' => $b64])),
        ]);
        $label = $adapter->getLabel('ECO-001');
        expect($label->base64)->toBe($b64);
    });

    it('getRates returns RateData array', function (): void {
        $response = [
            'tarifs' => [
                ['wilaya_id' => 16, 'wilaya_name' => 'Alger', 'home_price' => 450, 'stopdesk_price' => 200],
            ],
        ];

        $adapter = ecotrackAdapter([new Response(200, [], json_encode($response))]);
        $rates = $adapter->getRates(toWilayaId: 16);

        expect($rates)->toHaveCount(1)
            ->and($rates[0]->homeDeliveryPrice)->toBe(450.0)
            ->and($rates[0]->stopDeskPrice)->toBe(200.0)
            ->and($rates[0]->provider)->toBe(Provider::ECOTRACK);
    });

    it('normalizes all mapped Ecotrack statuses', function (): void {
        $adapter = ecotrackAdapter([]);

        $map = [
            'created' => TrackingStatus::PENDING,
            'Pending' => TrackingStatus::PENDING,
            'picked_up' => TrackingStatus::PICKED_UP,
            'ramassé' => TrackingStatus::PICKED_UP,
            'in_transit' => TrackingStatus::IN_TRANSIT,
            'En Transit' => TrackingStatus::IN_TRANSIT,
            'out_for_delivery' => TrackingStatus::OUT_FOR_DELIVERY,
            'delivered' => TrackingStatus::DELIVERED,
            'Livré' => TrackingStatus::DELIVERED,
            'delivery_failed' => TrackingStatus::FAILED_DELIVERY,
            'refused' => TrackingStatus::FAILED_DELIVERY,
            'returning' => TrackingStatus::RETURNING,
            'returned' => TrackingStatus::RETURNED,
            'cancelled' => TrackingStatus::CANCELLED,
            'stop_desk' => TrackingStatus::READY_FOR_PICKUP,
            'exception' => TrackingStatus::EXCEPTION,
            'lost' => TrackingStatus::EXCEPTION,
            'nope-nope' => TrackingStatus::UNKNOWN,
        ];

        foreach ($map as $raw => $expected) {
            expect($adapter->normalizeStatus($raw))->toBe($expected, "Mapping failed for: {$raw}");
        }
    });

});
