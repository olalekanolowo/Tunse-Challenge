<?php

namespace App\Services;

class PhoneNormalizer
{
    /**
     * Normalize a Nigerian phone number to the canonical +234XXXXXXXXXX format.
     *
     * Accepts local (0801...), international with plus (+234801...), or
     * international without plus (234801...) formats, with optional spaces/dashes.
     */
    public function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '234')) {
            $digits = substr($digits, 3);
        } elseif (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return '+234'.$digits;
    }
}
