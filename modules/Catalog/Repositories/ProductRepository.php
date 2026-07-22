<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Catalog\Repositories;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Models\Address;
use ModulesShoppingComplex\Shared\Repositories\BasePageRepository;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProductRepository extends BasePageRepository
{
    /**
     * Get all products with pagination and filtering
     *
     * @param  array<string, mixed>  $locationFilters  Optional lat/lon/radius for nearby filtering
     */
    public function list(int $perPage = 15, array $locationFilters = []): LengthAwarePaginator
    {
        $latitude = isset($locationFilters['latitude']) ? (float) $locationFilters['latitude'] : null;
        $longitude = isset($locationFilters['longitude']) ? (float) $locationFilters['longitude'] : null;
        $radius = isset($locationFilters['radius']) ? (float) $locationFilters['radius'] : 50;

        $baseQuery = Product::where('is_active', true);

        if ($latitude && $longitude) {
            $addressTable = Address::getTableName();
            $haversine = "(6371 * acos(
                cos(radians(?)) *
                cos(radians(COALESCE({$addressTable}.latitude, 0))) *
                cos(radians(COALESCE({$addressTable}.longitude, 0)) - radians(?)) +
                sin(radians(?)) *
                sin(radians(COALESCE({$addressTable}.latitude, 0)))
            ))";

            $nearbyVendorIds = DB::table('users')
                ->leftJoin($addressTable, 'users.id', '=', "{$addressTable}.user_id")
                ->where('users.role', 'vendor')
                ->whereRaw("{$haversine} <= ?", [$latitude, $longitude, $latitude, $radius])
                ->pluck('users.id');

            $baseQuery->whereIn('vendor_id', $nearbyVendorIds);
        }

        return QueryBuilder::for($baseQuery)
            ->with(['media', 'vendor'])
            ->allowedFilters([
                AllowedFilter::exact('category_id'),
                AllowedFilter::callback('name', function ($query, $value) {
                    $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $value);
                    $query->where(function ($q) use ($escaped) {
                        $q->where('name', 'like', "%{$escaped}%")
                            ->orWhereRaw(
                                "JSON_SEARCH(LOWER(COALESCE(tags, '[]')), 'one', ?) IS NOT NULL",
                                ['%'.strtolower($escaped).'%']
                            );
                    });
                }),
                AllowedFilter::partial('description'),
                AllowedFilter::callback('min_price', fn ($query, $value) => $query->where('price', '>=', (float) $value)),
                AllowedFilter::callback('max_price', fn ($query, $value) => $query->where('price', '<=', (float) $value)),
            ])
            ->allowedSorts([
                'id',
                'name',
                'price',
                'stock',
                'is_active',
                'created_at',
            ])
            ->allowedIncludes([
                'category',
            ])
            ->defaultSort('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Create a new product
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            return Product::create($data);
        });
    }

    /**
     * Update a product
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ModelNotFoundException
     */
    public function update(int $id, array $data): Product
    {
        return DB::transaction(function () use ($id, $data) {
            $product = $this->find($id);
            $product->update($data);

            return $product;
        });
    }

    /**
     * Delete a product (soft delete)
     *
     * @throws ModelNotFoundException
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $product = $this->find($id);

            return $product->delete();
        });
    }

    /**
     * Get products by vendor
     *
     * @param  array<string>  $relations
     */
    public function getByVendor(int $vendorId, array $relations = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Product::query()->where('vendor_id', $vendorId);

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get active products
     *
     * @param  array<string>  $relations
     */
    public function getActive(array $relations = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Product::query()->where('is_active', true);

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * Search products by name or description
     *
     * @param  array<string>  $relations
     */
    public function search(string $searchTerm, array $relations = [], int $perPage = 20): LengthAwarePaginator
    {
        $searchTerm = trim($searchTerm);
        $escapedTerm = str_replace(['%', '_'], ['\\%', '\\_'], $searchTerm);

        if (empty($searchTerm)) {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        $query = Product::query()
            ->where(function ($q) use ($escapedTerm) {
                $q->where('name', 'like', "%{$escapedTerm}%")
                    ->orWhere('description', 'like', "%{$escapedTerm}%")
                    ->orWhereRaw(
                        "JSON_SEARCH(LOWER(COALESCE(tags, '[]')), 'one', ?) IS NOT NULL",
                        ['%'.strtolower($escapedTerm).'%']
                    );
            });

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * Find a product by ID
     *
     * @param  array<string>  $relations  Relations to eager load
     *
     * @throws ModelNotFoundException
     */
    public function find(int $id, array $relations = []): Product
    {
        $query = Product::query();

        if (! empty($relations)) {
            $query->with($relations);
        }

        $product = $query->find($id);

        if (! $product) {
            throw new ModelNotFoundException("Product with ID {$id} not found.");
        }

        return $product;
    }
}
