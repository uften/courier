<?php

declare(strict_types=1);

namespace Uften\Courier\Data;

use Uften\Courier\Enums\LabelType;
use Uften\Courier\Enums\Provider;

/**
 * Unified shipping label response.
 *
 * Contains either a base64-encoded PDF blob or a URL — never both at the
 * same time. Use $type to determine which field is populated.
 */
final readonly class LabelData
{
    public function __construct(
        public Provider $provider,
        public string $trackingNumber,
        public LabelType $type,

        /** Populated when type === LabelType::PDF_BASE64. */
        public ?string $base64 = null,

        /** Populated when type === LabelType::PDF_URL or IMAGE_URL. */
        public ?string $url = null,
    ) {}

    /**
     * Build from a base64-encoded PDF blob.
     */
    public static function fromBase64(
        Provider $provider,
        string $trackingNumber,
        string $base64,
    ): self {
        return new self(
            provider: $provider,
            trackingNumber: $trackingNumber,
            type: LabelType::PDF_BASE64,
            base64: $base64,
        );
    }

    /**
     * Build from a PDF / image URL.
     */
    public static function fromUrl(
        Provider $provider,
        string $trackingNumber,
        string $url,
        LabelType $type = LabelType::PDF_URL,
    ): self {
        return new self(
            provider: $provider,
            trackingNumber: $trackingNumber,
            type: $type,
            url: $url,
        );
    }

    /**
     * Decode the base64 blob to raw binary PDF bytes.
     *
     * @throws \RuntimeException if label is not a base64 type.
     */
    public function decodePdf(): string
    {
        if ($this->type !== LabelType::PDF_BASE64 || $this->base64 === null) {
            throw new \RuntimeException(
                'Cannot decode PDF: label is not of type PDF_BASE64.',
            );
        }

        $decoded = base64_decode($this->base64, strict: true);

        if ($decoded === false) {
            throw new \RuntimeException('Failed to decode base64 label data.');
        }

        return $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider->value,
            'tracking_number' => $this->trackingNumber,
            'type' => $this->type->value,
            'base64' => $this->base64,
            'url' => $this->url,
        ];
    }
}
