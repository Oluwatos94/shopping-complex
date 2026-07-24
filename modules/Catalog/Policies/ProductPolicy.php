<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Catalog\Policies;

use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Identity\Models\User;

class ProductPolicy
{
    /**
     * Determine if the user can view any products.
     */
    public function viewAny(User $user): bool
    {
        // Only vendors and admins can view the product management interface
        return in_array($user->role, ['vendor', 'admin']);
    }

    /**
     * Determine if the user can view the product.
     */
    public function view(User $user, Product $product): bool
    {
        // Admins can view any product
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'vendor' && $product->vendor_id === $user->id;
    }

    /**
     * Determine if the user can create products.
     */
    public function create(User $user): bool
    {
        // Admins can always create products
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'vendor' && $this->isVendorVerified($user);
    }

    /**
     * Determine if the user can update the product.
     */
    public function update(User $user, Product $product): bool
    {
        // Admins can update any product
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'vendor'
            && $product->vendor_id === $user->id
            && $this->isVendorVerified($user);
    }

    /**
     * Determine if the user can delete the product.
     */
    public function delete(User $user, Product $product): bool
    {
        // Admins can delete any product
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'vendor'
            && $product->vendor_id === $user->id
            && $this->isVendorVerified($user);
    }

    /**
     * Determine if the user can restore the product.
     */
    public function restore(User $user, Product $product): bool
    {
        // Same logic as delete
        return $this->delete($user, $product);
    }

    /**
     * Determine if the user can permanently delete the product.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        // Only admins can force delete
        return $user->role === 'admin';
    }

    /**
     * Check if the vendor has completed verification (approved onboarding).
     */
    private function isVendorVerified(User $user): bool
    {
        return $user->isVendorVerified();
    }
}
