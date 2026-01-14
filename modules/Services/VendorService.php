<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use ModulesShoppingComplex\Repositories\VendorRepository;

final readonly class VendorService
{
    public function __construct(
        private VendorRepository $vendorRepository
    ) {}

    /**
     * Get nearby vendors with pagination and filtering
     *
     * @param  array<string, mixed>  $filters
     */
    public function getNearbyVendors(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        return $this->vendorRepository->findNearby($filters, $perPage);
    }

    /**
     * Get vendor statistics
     *
     * @return array<string, mixed>
     */
    public function getVendorStats(int $vendorId): array
    {
        return $this->vendorRepository->getStats($vendorId);
    }
}
