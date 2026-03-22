<?php

declare(strict_types=1);

namespace Uften\Courier\Exceptions;

/**
 * Thrown when a tracking number yields no result from the provider.
 */
final class OrderNotFoundException extends CourierException
{
    public function __construct(string $trackingNumber, ?\Throwable $previous = null)
    {
        parent::__construct(
            "Order with tracking number [{$trackingNumber}] was not found.",
            404,
            $previous,
        );
    }
}
