<?php

declare(strict_types=1);

namespace Uften\Courier\Data\Credentials;

/**
 * Single-token credentials used by Maystro and Ecotrack.
 */
final readonly class TokenCredentials
{
    public function __construct(
        public string $token,
    ) {}

    /** @param array<string, string> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            token: $data['token'] ?? throw new \InvalidArgumentException('Credentials require a "token".'),
        );
    }
}
