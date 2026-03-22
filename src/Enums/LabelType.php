<?php

declare(strict_types=1);

namespace Uften\Courier\Enums;

/**
 * The format in which a shipping label is returned by the provider.
 */
enum LabelType: string
{
    /** The label is returned as a base64-encoded PDF string. */
    case PDF_BASE64 = 'pdf_base64';

    /** The label is returned as a URL pointing to a PDF file. */
    case PDF_URL = 'pdf_url';

    /** The label is returned as a URL pointing to an image. */
    case IMAGE_URL = 'image_url';

    public function label(): string
    {
        return match ($this) {
            self::PDF_BASE64 => 'PDF (Base64 encoded)',
            self::PDF_URL => 'PDF (URL)',
            self::IMAGE_URL => 'Image (URL)',
        };
    }
}
