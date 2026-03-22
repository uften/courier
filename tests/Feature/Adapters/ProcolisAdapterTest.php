<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Uften\Courier\Adapters\ProcolisAdapter;
use Uften\Courier\Data\CreateOrderData;
use Uften\Courier\Data\Credentials\ProcolisCredentials;
use Uften\Courier\Enums\Provider;
use Uften\Courier\Enums\TrackingStatus;
use Uften\Courier\Exceptions\OrderNotFoundException;
use Uften\Courier\Exceptions\UnsupportedOperationException;

function procolisAdapter(array $responses, Provider $provider = Provider::PROCOLIS): ProcolisAdapter
{
    $client = new Client([
        'handler' => HandlerStack::create(new MockHandler($responses)),
        'http_errors' => false,
    ]);

    return new ProcolisAdapter(
        credentials: new ProcolisCredentials('test-id', 'test-token'),
        resolvedProvider: $provider,
        httpClient: $client,
    );
}

describe('ProcolisAdapter', function (): void {

    it('belongs to the PROCOLIS provider', function (): void {
        expect(procolisAdapter([])->provider())->toBe(Provider::PROCOLIS);
    });

    it('can also represent ZREXPRESS', function (): void {
        $adapter = procolisAdapter([], Provider::ZREXPRESS);
        expect($adapter->provider())->toBe(Provider::ZREXPRESS);
    });

    it('testCredentials returns true on success', function (): void {
        $adapter = procolisAdapter([
            new Response(200, [], json_encode(['livraisons' => []])),
        ]);
        expect($adapter->testCredentials())->toBeTrue();
    });

    it('createOrder returns OrderData with Procolis-style field names', function (): void {
        $apiResponse = [
            'Tracking' => 'PROC-123',
            'Client' => 'Mohamed Amrani',
            'MobileA' => '0551234567',
            'Adresse' => 'Rue des Roses',
            'IDWilaya' => 16,
            'Commune' => 'Alger Centre',
            'Total' => 12000,
            'Statut' => 'En attente',
        ];

        $adapter = procolisAdapter([new Response(200, [], json_encode($apiResponse))]);

        $order = $adapter->createOrder(new CreateOrderData(
            orderId: 'PROC-123',
            firstName: 'Mohamed',
            lastName: 'Amrani',
            phone: '0551234567',
            address: 'Rue des Roses',
            toWilayaId: 16,
            toCommune: 'Alger Centre',
            productDescription: 'Phone',
            price: 12000.0,
        ));

        expect($order->provider)->toBe(Provider::PROCOLIS)
            ->and($order->trackingNumber)->toBe('PROC-123')
            ->and($order->status)->toBe(TrackingStatus::PENDING)
            ->and($order->price)->toBe(12000.0);
    });

    it('getOrder throws OrderNotFoundException when error key is set', function (): void {
        $adapter = procolisAdapter([
            new Response(200, [], json_encode(['error' => true])),
        ]);
        expect(fn () => $adapter->getOrder('GHOST'))->toThrow(OrderNotFoundException::class);
    });

    it('getOrder throws OrderNotFoundException on empty response', function (): void {
        $adapter = procolisAdapter([new Response(200, [], '{}')]);
        expect(fn () => $adapter->getOrder('GHOST'))->toThrow(OrderNotFoundException::class);
    });

    it('getLabel throws UnsupportedOperationException', function (): void {
        $adapter = procolisAdapter([]);
        expect(fn () => $adapter->getLabel('TRK-001'))
            ->toThrow(UnsupportedOperationException::class);
    });

    it('getRates returns RateData array filtered by wilaya', function (): void {
        $ratesResponse = [
            'tarifs' => [
                ['wilaya_id' => 9,  'wilaya_name' => 'Blida', 'tarif_domicile' => 350, 'tarif_bureau' => 150],
                ['wilaya_id' => 16, 'wilaya_name' => 'Alger', 'tarif_domicile' => 400, 'tarif_bureau' => 200],
            ],
        ];

        $adapter = procolisAdapter([new Response(200, [], json_encode($ratesResponse))]);
        $rates = $adapter->getRates(toWilayaId: 9);

        expect($rates)->toHaveCount(2)
            ->and($rates[0]->homeDeliveryPrice)->toBe(350.0)
            ->and($rates[0]->stopDeskPrice)->toBe(150.0);
    });

    it('normalizes all mapped Procolis statuses', function (): void {
        $adapter = procolisAdapter([]);

        $map = [
            'en attente' => TrackingStatus::PENDING,
            'Ramassé' => TrackingStatus::PICKED_UP,
            'En transit' => TrackingStatus::IN_TRANSIT,
            'Sorti en livraison' => TrackingStatus::OUT_FOR_DELIVERY,
            'Livré' => TrackingStatus::DELIVERED,
            'Tentative échouée' => TrackingStatus::FAILED_DELIVERY,
            'Retour en cours' => TrackingStatus::RETURNING,
            'Retourné' => TrackingStatus::RETURNED,
            'Annulé' => TrackingStatus::CANCELLED,
            'Perdu' => TrackingStatus::EXCEPTION,
            'weird-status-xyz' => TrackingStatus::UNKNOWN,
        ];

        foreach ($map as $raw => $expected) {
            expect($adapter->normalizeStatus($raw))->toBe($expected);
        }
    });

});
