<?php

declare(strict_types=1);

namespace Uften\Courier\Enums;

/**
 * Delivery type options shared across providers.
 */
enum DeliveryType: int
{
    /** Standard home delivery. */
    case HOME = 1;

    /** Delivery to a stop-desk / pick-up point. */
    case STOP_DESK = 2;

    public function label(): string
    {
        return match ($this) {
            self::HOME => 'Home Delivery',
            self::STOP_DESK => 'Stop Desk / Pick-up Point',
        };
    }

    public function labelFr(): string
    {
        return match ($this) {
            self::HOME => 'Livraison à domicile',
            self::STOP_DESK => 'Stop desk / Point relais',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::HOME => 'توصيل للمنزل',
            self::STOP_DESK => 'توصيل للمكتب',
        };
    }
}
