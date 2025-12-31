<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Repositories;

use App\Repositories\BasePageRepository;
use Illuminate\Database\Eloquent\Collection;
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
        return QueryBuilder::for(Product::class)
            ->allowedFilters([
                AllowedFilter::exact('vendor_id'),
                AllowedFilter::exact('category_id'),
                AllowedFilter::exact('is_active'),
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
                'vendor',
                'category',
                'reviews',
                'media',
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
     * @return Collection<int, Product>
     */
    public function getByVendor(int $vendorId, array $relations = []): Collection
    {
        $query = Product::query()->where('vendor_id', $vendorId);

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->get();
    }

    /**
     * Get active products
     *
     * @param  array<string>  $relations
     * @return Collection<int, Product>
     */
    public function getActive(array $relations = []): Collection
    {
        $query = Product::query()->where('is_active', true);

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->get();
    }

    /**
     * Search products by name or description
     *
     * @param  array<string>  $relations
     * @return Collection<int, Product>
     */
    public function search(string $searchTerm, array $relations = []): Collection
    {
        $searchTerm = trim($searchTerm);

        if (empty($searchTerm)) {
            return new Collection;
        }

        $query = Product::query()
            ->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%");
            });

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->get();
    }

    /**
     * Update product stock
     *
     * @throws ModelNotFoundException
     */
    public function updateStock(int $id, int $quantity): Product
    {
        return DB::transaction(function () use ($id, $quantity) {
            $product = $this->find($id);
            $product->update(['stock' => $quantity]);

            return $product;
        });
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
