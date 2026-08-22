<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Identity\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
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
     * The id-side counterpart of {@see User::verifiedReferrals()}.
     *
     * @return Builder<User>
     */
    private function registeredReferrals(int $referrerId): Builder
    {
        return User::query()
            ->where('referred_by', $referrerId)
            ->whereNotNull('email_verified_at');
    }

    public function countParticipants(): int
    {
        return $this->participants()->count();
    }

    /**
     * @return Collection<int, User>
     */
    public function topStandings(int $limit): Collection
    {
        return $this->orderedStandings()->limit($limit)->get();
    }

    public function standingFor(int $vendorId): ?User
    {
        return $this->standings()->whereKey($vendorId)->first();
    }

    public function countStandingsAhead(int $vendorId, int $referralCount, string $lastReferralAt): int
    {
        return DB::query()
            ->fromSub($this->standings(), 'standings')
            ->where(function (QueryBuilder $query) use ($vendorId, $referralCount, $lastReferralAt): void {
                $query->where('verified_referrals_count', '>', $referralCount)
                    ->orWhere(function (QueryBuilder $tied) use ($vendorId, $referralCount, $lastReferralAt): void {
                        $tied->where('verified_referrals_count', $referralCount)
                            ->where(function (QueryBuilder $sooner) use ($vendorId, $lastReferralAt): void {
                                $sooner->where('verified_referrals_max_created_at', '<', $lastReferralAt)
                                    ->orWhere(function (QueryBuilder $tiedMoment) use ($vendorId, $lastReferralAt): void {
                                        $tiedMoment->where('verified_referrals_max_created_at', $lastReferralAt)
                                            ->where('id', '<', $vendorId);
                                    });
                            });
                    });
            })
            ->count();
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

    /**
     * @return Builder<User>
     */
    private function participants(): Builder
    {
        return User::query()
            ->where('role', 'vendor')
            ->whereHas('verifiedReferrals');
    }

    /**
     * @return Builder<User>
     */
    private function standings(): Builder
    {
        return $this->participants()
            ->select(['id', 'name', 'business_name'])
            ->withCount('verifiedReferrals')
            ->withMax('verifiedReferrals', 'created_at');
    }

    /**
     * @return Builder<User>
     */
    private function orderedStandings(): Builder
    {
        return $this->standings()
            ->orderByDesc('verified_referrals_count')
            ->orderBy('verified_referrals_max_created_at')
            ->orderBy('id');
    }
}
