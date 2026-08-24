<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Shared\Pagination;

final class PageSize
{
    public static function resolve(
        mixed $requested,
        int $default = PaginatorServiceInterface::PER_PAGE,
        int $max = PaginatorServiceInterface::MAX_PER_PAGE,
    ): int {
        if (! is_numeric($requested)) {
            return $default;
        }

        return min(max((int) $requested, 1), $max);
    }
}
