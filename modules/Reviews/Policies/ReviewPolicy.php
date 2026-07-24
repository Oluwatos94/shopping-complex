<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Reviews\Policies;

use ModulesShoppingComplex\Identity\Enums\UserEnum;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Reviews\Models\Review;

class ReviewPolicy
{
    /**
     * Determine whether the user can view any reviews.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the review.
     */
    public function view(?User $user, Review $review): bool
    {
        // Approved reviews are public
        if ($review->isApproved()) {
            return true;
        }

        if ($user && $user->id === $review->customer_id) {
            return true;
        }

        if ($user && $user->id === $review->vendor_id) {
            return true;
        }

        if ($user && $user->role === UserEnum::ADMIN->value) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create reviews.
     */
    public function create(User $user): bool
    {
        // Only customers can create reviews
        return $user->role === UserEnum::CUSTOMER->value;
    }

    /**
     * Determine whether the user can update the review.
     */
    public function update(User $user, Review $review): bool
    {
        // Only the author can update their review
        return $user->id === $review->customer_id;
    }

    /**
     * Determine whether the user can delete the review.
     */
    public function delete(User $user, Review $review): bool
    {
        if ($user->id === $review->customer_id) {
            return true;
        }

        if ($user->role === UserEnum::ADMIN->value) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can moderate reviews.
     */
    public function moderate(User $user): bool
    {
        return $user->role === UserEnum::ADMIN->value;
    }

    /**
     * Determine whether the user can respond to the review.
     */
    public function respond(User $user, Review $review): bool
    {
        return $user->id === $review->vendor_id && $review->isApproved();
    }

    /**
     * Determine whether the user can vote on the review.
     */
    public function vote(User $user, Review $review): bool
    {
        if (! $review->isApproved()) {
            return false;
        }

        // Author can't vote on their own review
        if ($user->id === $review->customer_id) {
            return false;
        }

        // Vendor can't vote on reviews about themselves
        if ($user->id === $review->vendor_id) {
            return false;
        }

        return true;
    }
}
