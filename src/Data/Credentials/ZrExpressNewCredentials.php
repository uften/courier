<?php

declare(strict_types=1);

namespace Uften\Courier\Data\Credentials;

/**
 * Credentials for the ZR Express NEW platform (api.zrexpress.app).
 *
 * Completely different from the legacy Procolis-based ZR Express credentials.
 * Both headers must be present on every request:
 *   X-Tenant  : your tenant UUID
 *   X-Api-Key : your API secret key
 */
final readonly class ZrExpressNewCredentials
{
    public function __construct(
        /** Tenant UUID — sent as the X-Tenant header. */
        public string $tenantId,

        /** Secret API key — sent as the X-Api-Key header. */
        public string $apiKey,
    ) {}

    /** @param array<string, string> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'] ?? $data['tenantId']
                ?? throw new \InvalidArgumentException('ZR Express NEW credentials require "tenant_id".'),
            apiKey: $data['api_key'] ?? $data['apiKey']
                ?? throw new \InvalidArgumentException('ZR Express NEW credentials require "api_key".'),
        );
    }
}
