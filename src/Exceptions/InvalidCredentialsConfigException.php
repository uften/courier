<?php

declare(strict_types=1);

namespace Uften\Courier\Exceptions;

use Uften\Courier\Enums\Provider;

/**
 * Thrown when the credentials array is missing required keys for a provider.
 */
final class InvalidCredentialsConfigException extends CourierException
{
    public function __construct(Provider $provider, string $detail, ?\Throwable $previous = null)
    {
        parent::__construct(
            "Invalid credentials for [{$provider->label()}]: {$detail}",
            0,
            $previous,
        );
    }
}
