<?php

declare(strict_types=1);

namespace Uften\Courier\Exceptions;

use Uften\Courier\Enums\Provider;

/**
 * Thrown when an operation is not supported by the selected provider.
 */
final class UnsupportedOperationException extends CourierException
{
    public function __construct(string $operation, Provider $provider, ?\Throwable $previous = null)
    {
        parent::__construct(
            "Operation [{$operation}] is not supported by the [{$provider->label()}] provider.",
            501,
            $previous,
        );
    }
}
