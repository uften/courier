<?php

declare(strict_types=1);

use Uften\Courier\Data\LabelData;
use Uften\Courier\Enums\LabelType;
use Uften\Courier\Enums\Provider;

describe('LabelData DTO', function (): void {

    it('builds correctly from a base64 string', function (): void {
        $b64 = base64_encode('%PDF-1.4 fake pdf content');

        $label = LabelData::fromBase64(Provider::YALIDINE, 'TRK-001', $b64);

        expect($label->provider)->toBe(Provider::YALIDINE)
            ->and($label->trackingNumber)->toBe('TRK-001')
            ->and($label->type)->toBe(LabelType::PDF_BASE64)
            ->and($label->base64)->toBe($b64)
            ->and($label->url)->toBeNull();
    });

    it('builds correctly from a URL', function (): void {
        $label = LabelData::fromUrl(Provider::MAYSTRO, 'TRK-002', 'https://cdn.example.com/label.pdf');

        expect($label->type)->toBe(LabelType::PDF_URL)
            ->and($label->url)->toBe('https://cdn.example.com/label.pdf')
            ->and($label->base64)->toBeNull();
    });

    it('decodes base64 to raw bytes correctly', function (): void {
        $content = '%PDF-1.4 fake pdf content';
        $label = LabelData::fromBase64(Provider::YALIDINE, 'TRK-003', base64_encode($content));

        expect($label->decodePdf())->toBe($content);
    });

    it('throws RuntimeException when decoding a URL-type label as PDF', function (): void {
        $label = LabelData::fromUrl(Provider::ECOTRACK, 'TRK-004', 'https://example.com/label.pdf');

        expect(fn () => $label->decodePdf())->toThrow(RuntimeException::class);
    });

    it('serializes to array correctly', function (): void {
        $label = LabelData::fromUrl(Provider::PROCOLIS, 'TRK-005', 'https://example.com/label.pdf');
        $array = $label->toArray();

        expect($array)->toHaveKey('provider', 'procolis')
            ->and($array)->toHaveKey('tracking_number', 'TRK-005')
            ->and($array)->toHaveKey('type', 'pdf_url')
            ->and($array)->toHaveKey('url', 'https://example.com/label.pdf')
            ->and($array)->toHaveKey('base64', null);
    });

});
