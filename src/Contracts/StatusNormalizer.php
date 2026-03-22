<?php

declare(strict_types=1);

namespace Uften\Courier\Contracts;

use Uften\Courier\Enums\TrackingStatus;

/**
 * Forces adapters to map provider-specific raw status strings
 * to our unified TrackingStatus enum.
 *
 * This is the heart of the normalisation layer: every adapter owns its own
 * mapping table, but the output is always a strongly-typed TrackingStatus.
 */
interface StatusNormalizer
{
    /**
     * Translate a raw provider status string into a canonical TrackingStatus.
     *
     * The mapping must be exhaustive: unrecognised strings MUST return
     * TrackingStatus::UNKNOWN rather than throwing, so that new statuses
     * introduced by the provider don't break existing applications.
     */
    public function normalizeStatus(string $rawStatus): TrackingStatus;
}
