<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use ModulesShoppingComplex\Models\Product;
use ModulesShoppingComplex\Repositories\ProductRepository;

final readonly class ProductService
{
    public function __construct(
        private ProductRepository $productRepository
    ) {}

    /**
     * Get all products with pagination
     *
     * Supports filtering, sorting, and including relations via query params:
     * - ?filter[name]=laptop
     * - ?filter[is_active]=1
     * - ?sort=-price
     * - ?include=vendor,category,media
     */
    public function index(int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->list($perPage);
    }

    /**
     * Get product by ID
     *
     * @throws ModelNotFoundException
     */
    public function getProduct(int $id): Product
    {
        return $this->productRepository->find($id, [
            'vendor',
            'category',
            'media',
        ]);
    }

    /**
     * Create a new product
     *
     * @param  array<string, mixed>  $data
     */
    public function createProduct(array $data): Product
    {
        // Generate slug if not provided
        if (! isset($data['slug']) && isset($data['name'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        return $this->productRepository->create($data);
    }

    /**
     * Update a product
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ModelNotFoundException
     */
    public function updateProduct(int $id, array $data): Product
    {
        // Update slug if name changed
        if (isset($data['name']) && ! isset($data['slug'])) {
            $currentProduct = $this->productRepository->find($id);
            $newSlug = Str::slug($data['name']);

            if ($newSlug !== $currentProduct->slug) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $id);
            }
        }

        return $this->productRepository->update($id, $data);
    }

    /**
     * Delete a product (soft delete)
     *
     * @throws ModelNotFoundException
     */
    public function deleteProduct(int $id): bool
    {
        return $this->productRepository->delete($id);
    }

    /**
     * Get products by vendor
     *
     * @return Collection<int, Product>
     */
    public function getVendorProducts(int $vendorId): Collection
    {
        return $this->productRepository->getByVendor($vendorId, [
            'category',
            'media',
        ]);
    }

    /**
     * Get active products
     *
     * @return Collection<int, Product>
     */
    public function getActiveProducts(): Collection
    {
        return $this->productRepository->getActive([
            'vendor',
            'category',
            'media',
        ]);
    }

    /**
     * Search products
     *
     * @return Collection<int, Product>
     */
    public function searchProducts(string $searchTerm): Collection
    {
        return $this->productRepository->search($searchTerm, [
            'vendor',
            'category',
            'media',
        ]);
    }

    /**
     * Update product stock
     *
     * @throws ModelNotFoundException
     */
    public function updateStock(int $id, int $quantity): Product
    {
        if ($quantity < 0) {
            throw new \InvalidArgumentException('Stock quantity cannot be negative.');
        }

        return $this->productRepository->updateStock($id, $quantity);
    }

    /**
     * Generate unique slug
     */
    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $query = Product::withTrashed()->where('slug', $slug);

            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }

            if (! $query->exists()) {
                break;
            }

            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
