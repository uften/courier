<?php

declare(strict_types=1);

namespace Uften\Courier\Data;

/**
 * Immutable metadata record for a shipping provider.
 *
 * Returned by Provider::metadata() and available via any adapter's metadata() method.
 * Useful for building provider-selection UIs, displaying logos, linking to tracking pages, etc.
 */
final readonly class ProviderMetadata
{
    public function __construct(
        /** Internal machine-readable identifier, e.g. "Yalidine". */
        public string $name,

        /** Human-readable display name, e.g. "Yalidine". */
        public string $title,

        /** Provider's public website. */
        public string $website,

        /** Short description of the provider. */
        public string $description,

        /** URL of the provider's logo image. Null when not publicly available. */
        public ?string $logo = null,

        /** URL to the provider's API documentation. */
        public ?string $apiDocs = null,

        /** URL to the provider's support page or contact. */
        public ?string $support = null,

        /**
         * Public tracking URL where end-customers can track their parcels.
         * Null when the provider does not expose a public tracking page.
         */
        public ?string $trackingUrl = null,
    ) {}

    /**
     * Build from the raw associative array format used in the original library.
     *
     * @param  array<string, string|null>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            website: (string) ($data['website'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            logo: isset($data['logo']) && $data['logo'] !== '#' ? (string) $data['logo'] : null,
            apiDocs: isset($data['api_docs']) && $data['api_docs'] ? (string) $data['api_docs'] : null,
            support: isset($data['support']) && $data['support'] ? (string) $data['support'] : null,
            trackingUrl: isset($data['tracking_url']) && $data['tracking_url'] ? (string) $data['tracking_url'] : null,
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'title' => $this->title,
            'website' => $this->website,
            'description' => $this->description,
            'logo' => $this->logo,
            'api_docs' => $this->apiDocs,
            'support' => $this->support,
            'tracking_url' => $this->trackingUrl,
        ];
    }
}
