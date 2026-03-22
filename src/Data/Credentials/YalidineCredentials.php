<?php

declare(strict_types=1);

namespace Uften\Courier\Data\Credentials;

/**
 * Yalidine credentials: token + key.
 */
final readonly class YalidineCredentials
{
    public function __construct(
        public string $token,
        public string $apiKey,
    ) {}

    /** @param array<string, string> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            token: $data['token'] ?? throw new \InvalidArgumentException('Yalidine credentials require a "token".'),
            apiKey: $data['key'] ?? throw new \InvalidArgumentException('Yalidine credentials require a "key".'),
        );
    }
}
