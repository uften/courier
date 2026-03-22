<?php

declare(strict_types=1);

use Uften\Courier\Enums\TrackingStatus;

describe('TrackingStatus enum', function (): void {

    it('has the correct backing values', function (): void {
        expect(TrackingStatus::PENDING->value)->toBe('pending')
            ->and(TrackingStatus::DELIVERED->value)->toBe('delivered')
            ->and(TrackingStatus::CANCELLED->value)->toBe('cancelled')
            ->and(TrackingStatus::UNKNOWN->value)->toBe('unknown');
    });

    it('can be created from a string value', function (): void {
        $status = TrackingStatus::from('delivered');
        expect($status)->toBe(TrackingStatus::DELIVERED);
    });

    it('returns null for unknown string via tryFrom', function (): void {
        $status = TrackingStatus::tryFrom('this-does-not-exist');
        expect($status)->toBeNull();
    });

    it('provides English labels for all cases', function (TrackingStatus $status): void {
        expect($status->label())->toBeString()->not->toBeEmpty();
    })->with(TrackingStatus::cases());

    it('provides French labels for all cases', function (TrackingStatus $status): void {
        expect($status->labelFr())->toBeString()->not->toBeEmpty();
    })->with(TrackingStatus::cases());

    it('provides Arabic labels for all cases', function (TrackingStatus $status): void {
        expect($status->labelAr())->toBeString()->not->toBeEmpty();
    })->with(TrackingStatus::cases());

    it('correctly identifies terminal statuses', function (): void {
        expect(TrackingStatus::DELIVERED->isTerminal())->toBeTrue()
            ->and(TrackingStatus::RETURNED->isTerminal())->toBeTrue()
            ->and(TrackingStatus::CANCELLED->isTerminal())->toBeTrue()
            ->and(TrackingStatus::IN_TRANSIT->isTerminal())->toBeFalse()
            ->and(TrackingStatus::PENDING->isTerminal())->toBeFalse();
    });

    it('correctly identifies successful delivery', function (): void {
        expect(TrackingStatus::DELIVERED->isSuccessful())->toBeTrue();

        $nonSuccessful = array_filter(
            TrackingStatus::cases(),
            fn (TrackingStatus $s): bool => $s !== TrackingStatus::DELIVERED,
        );

        foreach ($nonSuccessful as $status) {
            expect($status->isSuccessful())->toBeFalse();
        }
    });

    it('correctly identifies active statuses', function (): void {
        $active = [
            TrackingStatus::PICKED_UP,
            TrackingStatus::IN_TRANSIT,
            TrackingStatus::OUT_FOR_DELIVERY,
            TrackingStatus::RETURNING,
        ];

        foreach ($active as $status) {
            expect($status->isActive())->toBeTrue("Expected {$status->value} to be active");
        }

        expect(TrackingStatus::DELIVERED->isActive())->toBeFalse()
            ->and(TrackingStatus::PENDING->isActive())->toBeFalse()
            ->and(TrackingStatus::CANCELLED->isActive())->toBeFalse();
    });

    it('returns a non-empty colour for every case', function (TrackingStatus $status): void {
        expect($status->color())->toBeString()->not->toBeEmpty();
    })->with(TrackingStatus::cases());
});
