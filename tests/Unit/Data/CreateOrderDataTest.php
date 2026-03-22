<?php

declare(strict_types=1);

use Uften\Courier\Data\CreateOrderData;
use Uften\Courier\Enums\DeliveryType;

describe('CreateOrderData DTO', function (): void {

    it('constructs with required fields and correct defaults', function (): void {
        $dto = new CreateOrderData(
            orderId: 'ORD-001',
            firstName: 'Mohamed',
            lastName: 'Amrani',
            phone: '0551234567',
            address: 'Rue des Roses, Alger',
            toWilayaId: 16,
            toCommune: 'Alger Centre',
            productDescription: 'Smartphone',
            price: 12000.0,
        );

        expect($dto->orderId)->toBe('ORD-001')
            ->and($dto->firstName)->toBe('Mohamed')
            ->and($dto->lastName)->toBe('Amrani')
            ->and($dto->deliveryType)->toBe(DeliveryType::HOME)
            ->and($dto->freeShipping)->toBeFalse()
            ->and($dto->hasExchange)->toBeFalse()
            ->and($dto->exchangeProduct)->toBeNull()
            ->and($dto->stopDeskId)->toBeNull()
            ->and($dto->fromWilayaId)->toBeNull()
            ->and($dto->phoneAlt)->toBeNull()
            ->and($dto->notes)->toBeNull();
    });

    it('can be constructed from a snake_case array', function (): void {
        $dto = CreateOrderData::fromArray([
            'order_id'            => 'ORD-002',
            'first_name'          => 'Fatima',
            'last_name'           => 'Benali',
            'phone'               => '0661234567',
            'address'             => '12 Rue Didouche Mourad',
            'to_wilaya_id'        => 31,
            'to_commune'          => 'Oran',
            'product_description' => 'Laptop',
            'price'               => 85000,
            'delivery_type'       => 1,
            'notes'               => 'Leave at door',
        ]);

        expect($dto->orderId)->toBe('ORD-002')
            ->and($dto->firstName)->toBe('Fatima')
            ->and($dto->lastName)->toBe('Benali')
            ->and($dto->toWilayaId)->toBe(31)
            ->and($dto->price)->toBe(85000.0)
            ->and($dto->deliveryType)->toBe(DeliveryType::HOME)
            ->and($dto->notes)->toBe('Leave at door');
    });

    it('maps is_stopdesk flag to STOP_DESK delivery type', function (): void {
        $dto = CreateOrderData::fromArray([
            'order_id'            => 'ORD-003',
            'first_name'          => 'Ali',
            'last_name'           => 'Khelifi',
            'phone'               => '0771234567',
            'address'             => 'Agence Tizi',
            'to_wilaya_id'        => 15,
            'to_commune'          => 'Tizi Ouzou',
            'product_description' => 'Shoes',
            'price'               => 3500,
            'is_stopdesk'         => true,
        ]);

        expect($dto->deliveryType)->toBe(DeliveryType::STOP_DESK);
    });

    it('serializes back to a complete snake_case array', function (): void {
        $dto = new CreateOrderData(
            orderId: 'ORD-004',
            firstName: 'Youssef',
            lastName: 'Hadj',
            phone: '0551234567',
            address: 'Cité des Pins',
            toWilayaId: 9,
            toCommune: 'Blida',
            productDescription: 'Clothes',
            price: 2500.0,
            deliveryType: DeliveryType::STOP_DESK,
            stopDeskId: 42,
        );

        $array = $dto->toArray();

        expect($array)->toHaveKey('order_id', 'ORD-004')
            ->and($array)->toHaveKey('delivery_type', 2)
            ->and($array)->toHaveKey('stop_desk_id', 42)
            ->and($array)->toHaveKey('price', 2500.0);
    });

    it('is immutable (readonly properties)', function (): void {
        $dto = new CreateOrderData(
            orderId: 'ORD-005',
            firstName: 'Sara',
            lastName: 'Meziani',
            phone: '0661234567',
            address: 'Bab El Oued',
            toWilayaId: 16,
            toCommune: 'Alger',
            productDescription: 'Book',
            price: 500.0,
        );

        expect(fn() => $dto->orderId = 'CHANGED')->toThrow(\Error::class);
    });
});
