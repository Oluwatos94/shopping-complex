<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\WhatsApp\Support;

final class WhatsAppPhone
{
    /**
     * Normalize a stored number to the E.164 digits Meta expects (Nigerian
     * numbering plan). Returns null when it is not a valid NG mobile number.
     */
    public static function toE164(string $number): ?string
    {
        $digits = (string) preg_replace('/[^0-9]/', '', $number);

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        $national = match (true) {
            str_starts_with($digits, '2340') && strlen($digits) === 14 => substr($digits, 4),
            str_starts_with($digits, '234') && strlen($digits) === 13 => substr($digits, 3),
            str_starts_with($digits, '0') && strlen($digits) === 11 => substr($digits, 1),
            strlen($digits) === 10 => $digits,
            default => null,
        };

        if ($national === null || preg_match('/^[789]\d{9}$/', $national) !== 1) {
            return null;
        }

        return '234'.$national;
    }
}
