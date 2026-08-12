<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Identity\Repositories;

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
