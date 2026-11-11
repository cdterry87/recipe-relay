<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Rejects images with too many pixels before they are decoded. Decoding needs about four
 * bytes per pixel, so a small, highly compressed file with huge dimensions (a "decompression
 * bomb") could otherwise exhaust PHP's memory. Reads only the header.
 */
class MaxMegapixels implements ValidationRule
{
    public function __construct(private readonly int $megapixels) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $size = $value instanceof UploadedFile ? @getimagesize($value->getRealPath()) : false;

        if ($size === false || $this->megapixels * 1_000_000 < $size[0] * $size[1]) {
            $fail("Choose a smaller photo, up to {$this->megapixels} megapixels.");
        }
    }
}
