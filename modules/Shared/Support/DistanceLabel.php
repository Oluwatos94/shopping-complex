<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Shared\Support;

final class DistanceLabel
{
    public const PRECISE_METERS = 1000;

    public const USABLE_METERS = 10000;

    public static function format(float $km, ?float $accuracyMeters = null): ?string
    {
        if (! self::isUsable($accuracyMeters)) {
            return null;
        }

        if (self::isPrecise($accuracyMeters)) {
            $meters = (int) round($km * 1000);

            // number_format, not round: round(2.0, 1) concatenates as "2 km away".
            return $meters < 1000
                ? $meters.'m away'
                : number_format($km, 1).' km away';
        }

        return 'about '.max(1, (int) round($km)).' km away';
    }

    public static function isPrecise(?float $accuracyMeters): bool
    {
        return $accuracyMeters === null || $accuracyMeters <= self::PRECISE_METERS;
    }

    public static function isUsable(?float $accuracyMeters): bool
    {
        return $accuracyMeters === null || $accuracyMeters <= self::USABLE_METERS;
    }
}
