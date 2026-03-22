<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Uften\Courier\Adapters\MaystroAdapter;
use Uften\Courier\Data\CreateOrderData;
use Uften\Courier\Data\Credentials\TokenCredentials;
use Uften\Courier\Enums\Provider;
use Uften\Courier\Enums\TrackingStatus;
use Uften\Courier\Exceptions\OrderNotFoundException;

function maystroAdapter(array $responses): MaystroAdapter
{
    $client = new Client([
        'handler' => HandlerStack::create(new MockHandler($responses)),
        'http_errors' => false,
    ]);

    return new MaystroAdapter(
        credentials: new TokenCredentials('test-token'),
        httpClient: $client,
    );
}

describe('MaystroAdapter', function (): void {

    it('belongs to the MAYSTRO provider', function (): void {
        expect(maystroAdapter([])->provider())->toBe(Provider::MAYSTRO);
    });

    it('testCredentials returns true on success', function (): void {
        $adapter = maystroAdapter([new Response(200, [], json_encode(['data' => []]))]);
        expect($adapter->testCredentials())->toBeTrue();
    });

    it('testCredentials returns false on error', function (): void {
        $adapter = maystroAdapter([new Response(403, [], '{}')]);
        expect($adapter->testCredentials())->toBeFalse();
    });

    it('createOrder maps payload and returns OrderData', function (): void {
        $apiResponse = [
            'id' => 'MYST-001',
            'external_ref' => 'ORD-100',
            'tracking' => 'MYST-001',
            'customer_name' => 'Youssef Hadj',
            'customer_phone' => '0551234567',
            'address' => 'Cité des Pins, Blida',
            'wilaya' => 9,
            'commune' => 'Blida',
            'product_price' => 4500,
            'status' => 'pending',
            'created_at' => '2024-09-01T10:00:00Z',
        ];

        $adapter = maystroAdapter([new Response(200, [], json_encode($apiResponse))]);

        $order = $adapter->createOrder(new CreateOrderData(
            orderId: 'ORD-100',
            firstName: 'Youssef',
            lastName: 'Hadj',
            phone: '0551234567',
            address: 'Cité des Pins, Blida',
            toWilayaId: 9,
            toCommune: 'Blida',
            productDescription: 'Clothes',
            price: 4500.0,
        ));

        expect($order->provider)->toBe(Provider::MAYSTRO)
            ->and($order->status)->toBe(TrackingStatus::PENDING)
            ->and($order->recipientName)->toBe('Youssef Hadj')
            ->and($order->toWilayaId)->toBe(9)
            ->and($order->price)->toBe(4500.0)
            ->and($order->createdAt)->not->toBeNull();
    });

    it('getOrder throws OrderNotFoundException on empty body', function (): void {
        $adapter = maystroAdapter([new Response(200, [], '{}')]);
        expect(fn () => $adapter->getOrder('GHOST'))->toThrow(OrderNotFoundException::class);
    });

    it('normalizes all mapped Maystro statuses', function (): void {
        $adapter = maystroAdapter([]);

        $map = [
            'pending' => TrackingStatus::PENDING,
            'waiting_for_pickup' => TrackingStatus::PENDING,
            'picked_up' => TrackingStatus::PICKED_UP,
            'in_transit' => TrackingStatus::IN_TRANSIT,
            'out_for_delivery' => TrackingStatus::OUT_FOR_DELIVERY,
            'delivered' => TrackingStatus::DELIVERED,
            'delivery_failed' => TrackingStatus::FAILED_DELIVERY,
            'returning' => TrackingStatus::RETURNING,
            'returned' => TrackingStatus::RETURNED,
            'cancelled' => TrackingStatus::CANCELLED,
            'stop_desk' => TrackingStatus::READY_FOR_PICKUP,
            'lost' => TrackingStatus::EXCEPTION,
            'UNKNOWN_RAW' => TrackingStatus::UNKNOWN,
        ];

        foreach ($map as $raw => $expected) {
            expect($adapter->normalizeStatus($raw))->toBe($expected);
        }
    });

    it('getLabel returns base64 label', function (): void {
        $pdfContent = '%PDF-1.4 test content';
        $adapter = maystroAdapter([
            new Response(200, [], $pdfContent),
        ]);

        $label = $adapter->getLabel('MYST-001');

        expect($label->type->value)->toBe('pdf_base64')
            ->and($label->base64)->toBe(base64_encode($pdfContent));
    });

    it('validation rules contain all required fields', function (): void {
        $rules = maystroAdapter([])->getCreateOrderValidationRules();

        expect($rules)->toHaveKey('order_id')
            ->and($rules)->toHaveKey('phone')
            ->and($rules)->toHaveKey('to_wilaya_id')
            ->and($rules)->toHaveKey('price');
    });

});
