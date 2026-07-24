<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Shared\Http\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use ModulesShoppingComplex\Shared\Pagination\PaginatorServiceInterface;

trait PaginatesResults
{
    protected function paginatedResponse(LengthAwarePaginator $paginator, string $key = 'data'): JsonResponse
    {
        return response()->json([
            $key => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    protected function getPerPage(Request $request, int $default = PaginatorServiceInterface::PER_PAGE): int
    {
        return min((int) $request->get('per_page', $default), 50);
    }
}
