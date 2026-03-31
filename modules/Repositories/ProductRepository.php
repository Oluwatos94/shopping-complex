<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Repositories;

use App\Repositories\BasePageRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Models\Product;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProductRepository extends BasePageRepository
{
    /**
     * Get all products with pagination and filtering
     */
    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return QueryBuilder::for(Product::where('is_active', true))
            ->with(['media', 'vendor'])
            ->allowedFilters([
                AllowedFilter::exact('category_id'),
                AllowedFilter::partial('name'),
                AllowedFilter::partial('description'),
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
            ->defaultSort('-created_at')
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
                    ->orWhere('description', 'like', "%{$escapedTerm}%");
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
