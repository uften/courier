<?php

declare(strict_types=1);

namespace Uften\Courier\Enums;

/**
 * Unified tracking status dictionary.
 *
 * Every delivery company maps their proprietary/weird status strings to
 * one of these canonical values inside their adapter's `normalizeStatus()`
 * method. This guarantees that the calling application always gets a
 * predictable, type-safe status regardless of which provider is in use.
 */
enum TrackingStatus: string
{
    /** Order has been created but not yet picked up. */
    case PENDING = 'pending';

    /** The parcel has been collected from the sender. */
    case PICKED_UP = 'picked_up';

    /** Parcel is at a sorting hub / in transit between facilities. */
    case IN_TRANSIT = 'in_transit';

    /** Parcel is with a delivery agent, on its way to the recipient. */
    case OUT_FOR_DELIVERY = 'out_for_delivery';

    /** Parcel was successfully delivered to the recipient. */
    case DELIVERED = 'delivered';

    /** A delivery attempt was made but failed (absent, refused, etc.). */
    case FAILED_DELIVERY = 'failed_delivery';

    /** Parcel is on its way back to the original sender. */
    case RETURNING = 'returning';

    /** Parcel has been returned and received by the sender. */
    case RETURNED = 'returned';

    /** Order was explicitly cancelled before shipment. */
    case CANCELLED = 'cancelled';

    /** Parcel is held at an office/desk for pickup by the recipient. */
    case READY_FOR_PICKUP = 'ready_for_pickup';

    /** A problem occurred (damaged, lost, customs hold, etc.). */
    case EXCEPTION = 'exception';

    /** Status could not be mapped to any known internal value. */
    case UNKNOWN = 'unknown';

    // -------------------------------------------------------------------------
    // Convenience helpers
    // -------------------------------------------------------------------------

    /**
     * Human-readable English label for display in UIs or logs.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PICKED_UP => 'Picked Up',
            self::IN_TRANSIT => 'In Transit',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::DELIVERED => 'Delivered',
            self::FAILED_DELIVERY => 'Failed Delivery Attempt',
            self::RETURNING => 'Returning to Sender',
            self::RETURNED => 'Returned to Sender',
            self::CANCELLED => 'Cancelled',
            self::READY_FOR_PICKUP => 'Ready for Pickup',
            self::EXCEPTION => 'Exception / Issue',
            self::UNKNOWN => 'Unknown',
        };
    }

    /**
     * Human-readable French label (relevant for Algerian context).
     */
    public function labelFr(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::PICKED_UP => 'Collecté',
            self::IN_TRANSIT => 'En transit',
            self::OUT_FOR_DELIVERY => 'En cours de livraison',
            self::DELIVERED => 'Livré',
            self::FAILED_DELIVERY => 'Tentative de livraison échouée',
            self::RETURNING => 'En retour vers l\'expéditeur',
            self::RETURNED => 'Retourné à l\'expéditeur',
            self::CANCELLED => 'Annulé',
            self::READY_FOR_PICKUP => 'Disponible en point relais',
            self::EXCEPTION => 'Exception / Problème',
            self::UNKNOWN => 'Inconnu',
        };
    }

    /**
     * Human-readable Arabic label (relevant for Algerian context).
     */
    public function labelAr(): string
    {
        return match ($this) {
            self::PENDING => 'قيد الانتظار',
            self::PICKED_UP => 'تم الاستلام',
            self::IN_TRANSIT => 'قيد النقل',
            self::OUT_FOR_DELIVERY => 'قيد التوصيل',
            self::DELIVERED => 'تم التوصيل',
            self::FAILED_DELIVERY => 'فشل التوصيل',
            self::RETURNING => 'قيد الإرجاع',
            self::RETURNED => 'تم الإرجاع',
            self::CANCELLED => 'تم الإلغاء',
            self::READY_FOR_PICKUP => 'جاهز للاستلام',
            self::EXCEPTION => 'استثناء / مشكلة',
            self::UNKNOWN => 'غير معروف',
        };
    }

    /**
     * Whether this status represents a terminal (final) state.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::DELIVERED, self::RETURNED, self::CANCELLED => true,
            default => false,
        };
    }

    /**
     * Whether this status represents a successful delivery.
     */
    public function isSuccessful(): bool
    {
        return $this === self::DELIVERED;
    }

    /**
     * Whether this status indicates the parcel is still moving.
     */
    public function isActive(): bool
    {
        return match ($this) {
            self::PICKED_UP, self::IN_TRANSIT,
            self::OUT_FOR_DELIVERY, self::RETURNING => true,
            default => false,
        };
    }

    /**
     * Returns a colour hint useful for badge rendering in UIs.
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::PICKED_UP => 'blue',
            self::IN_TRANSIT => 'indigo',
            self::OUT_FOR_DELIVERY => 'yellow',
            self::DELIVERED => 'green',
            self::FAILED_DELIVERY => 'orange',
            self::RETURNING => 'amber',
            self::RETURNED => 'red',
            self::CANCELLED => 'red',
            self::READY_FOR_PICKUP => 'teal',
            self::EXCEPTION => 'rose',
            self::UNKNOWN => 'gray',
        };
    }
}
