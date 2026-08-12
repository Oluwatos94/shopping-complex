<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Identity\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use ModulesShoppingComplex\Identity\Models\User;

class ReferralRepository
{
    public function findByCode(string $code): ?User
    {
        return User::query()->where('referral_code', $code)->first();
    }

    public function getCode(int $userId): ?string
    {
        /** @var string|null $code */
        $code = User::query()->whereKey($userId)->value('referral_code');

        return $code;
    }

    public function claimCode(int $userId, string $code): bool
    {
        return User::query()
            ->whereKey($userId)
            ->whereNull('referral_code')
            ->update(['referral_code' => $code]) > 0;
    }

    public function attachReferrer(int $userId, int $referrerId): bool
    {
        return User::query()
            ->whereKey($userId)
            ->whereNull('referred_by')
            ->update(['referred_by' => $referrerId]) > 0;
    }

    public function countReferrals(int $referrerId): int
    {
        return $this->registeredReferrals($referrerId)->count();
    }

    /**
     * @return Collection<int, User>
     */
    public function recentReferrals(int $referrerId, int $limit): Collection
    {
        return $this->registeredReferrals($referrerId)
            ->select(['id', 'name', 'created_at'])
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Unverified signups are excluded: nobody has proven they own the mailbox,
     * so counting them would let a vendor inflate their own total.
     *
     * @return Builder<User>
     */
    private function registeredReferrals(int $referrerId): Builder
    {
        return User::query()
            ->where('referred_by', $referrerId)
            ->whereNotNull('email_verified_at');
    }

    /**
     * @param  callable(Collection<int, User>): void  $callback
     */
    public function chunkVendorsWithoutCode(int $chunkSize, callable $callback): void
    {
        User::query()
            ->select(['id', 'referral_code'])
            ->where('role', 'vendor')
            ->whereNull('referral_code')
            ->chunkById($chunkSize, $callback);
    }
}
