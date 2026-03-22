<?php

declare(strict_types=1);

namespace Uften\Courier\Data\Credentials;

/**
 * Procolis / ZR Express credentials: id + token pair.
 */
final readonly class ProcolisCredentials
{
    public function __construct(
        public string $id,
        public string $token,
    ) {}

    /** @param array<string, string> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? throw new \InvalidArgumentException('Procolis credentials require an "id".'),
            token: $data['token'] ?? throw new \InvalidArgumentException('Procolis credentials require a "token".'),
        );
    }
}
