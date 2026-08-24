<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Shared\Support;

final class LikeTerm
{
    /**
     * Neutralise LIKE wildcards so a search term matches literally. Backslashes
     * go first: escaping them afterwards would re-arm the wildcards they
     * precede, which is how "\%" slips through a naive replace.
     */
    public static function escape(string $term): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
    }
}
