<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Identity\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Shared\Support\LikeTerm;
use stdClass;

class ReferralRepository
{
    private const RANKING_ORDER = 'qualified_referrals_count desc, qualified_at asc, id asc';

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
     * @return array{qualified: int, referred: int}
     */
    public function referralTally(int $referrerId): array
    {
        $tally = DB::query()
            ->fromSub($this->tallyByReferrer($referrerId), 'tally')
            ->first();

        return [
            'qualified' => (int) ($tally?->qualified_referrals_count ?? 0),
            'referred' => (int) ($tally?->referred_vendors_count ?? 0),
        ];
    }

    private function tallyByReferrer(?int $referrerId = null): QueryBuilder
    {
        return DB::query()
            ->fromSub($this->referredVendorsWithQualification($referrerId), 'referred_vendors')
            ->select('referred_by')
            ->selectRaw('count(*) as referred_vendors_count')
            ->selectRaw('count(qualified_at) as qualified_referrals_count')
            ->selectRaw('max(qualified_at) as qualified_at')
            ->groupBy('referred_by');
    }

    private function referredVendorsWithQualification(?int $referrerId = null): QueryBuilder
    {
        $nthProduct = Product::query()
            ->select('products.created_at')
            ->whereColumn('products.vendor_id', 'referred.id')
            ->orderBy('products.created_at')
            ->offset(max(0, User::minReferralProducts() - 1))
            ->limit(1);

        $referred = DB::table('users as referred')
            ->select('referred.referred_by')
            ->selectSub($nthProduct, 'qualified_at');

        $this->onlyReferredVendors($referred, 'referred');

        if ($referrerId !== null) {
            $referred->where('referred.referred_by', $referrerId);
        }

        return $referred;
    }

    /**
     * The one definition of "a business referred into the campaign". Applied to
     * both the correlated subquery and the plain id-side lookup below.
     *
     * @param  QueryBuilder|Builder<User>  $query
     */
    private function onlyReferredVendors(QueryBuilder|Builder $query, string $table): void
    {
        $query->where("{$table}.role", 'vendor')->whereNotNull("{$table}.email_verified_at");
    }

    /**
     * @return Collection<int, User>
     */
    public function recentReferrals(int $referrerId, int $limit): Collection
    {
        $referred = User::query()->where('users.referred_by', $referrerId);

        $this->onlyReferredVendors($referred, 'users');

        return $referred
            ->select(['id', 'name', 'created_at'])
            ->withCount('products')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    public function countParticipants(): int
    {
        return $this->standings()->count();
    }

    /**
     * @return Collection<int, User>
     */
    public function topStandings(int $limit): Collection
    {
        return $this->orderedStandings()->limit($limit)->get();
    }

    public function standingFor(int $vendorId): ?stdClass
    {
        return $this->rankedStandings()->where('id', $vendorId)->first();
    }

    /**
     * @return LengthAwarePaginator<int, stdClass>
     */
    public function paginateParticipants(?string $search, int $perPage): LengthAwarePaginator
    {
        $participants = $this->rankedStandings();

        if ($search !== null && $search !== '') {
            $escaped = LikeTerm::escape($search);

            $participants->where(function (QueryBuilder $match) use ($escaped): void {
                $match->where('name', 'like', "%{$escaped}%")
                    ->orWhere('email', 'like', "%{$escaped}%")
                    ->orWhere('business_name', 'like', "%{$escaped}%");
            });
        }

        return $participants->orderBy('campaign_rank')->paginate($perPage);
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
     * Every vendor with at least one counting referral, joined to their tally.
     * The join is what makes a participant a participant — no referrals, no row.
     *
     * @return Builder<User>
     */
    private function standings(): Builder
    {
        return User::query()
            ->joinSub($this->tallyByReferrer(), 'tally', 'tally.referred_by', '=', 'users.id')
            ->where('users.role', 'vendor')
            ->where('tally.qualified_referrals_count', '>=', 1)
            ->select([
                'users.id',
                'users.name',
                'users.business_name',
                'users.email',
                'users.created_at',
                'tally.qualified_referrals_count',
                'tally.referred_vendors_count',
                'tally.qualified_at',
            ]);
    }

    /**
     * @return Builder<User>
     */
    private function orderedStandings(): Builder
    {
        return $this->standings()->orderByRaw(self::RANKING_ORDER);
    }

    private function rankedStandings(): QueryBuilder
    {
        $ranked = DB::query()
            ->fromSub($this->standings(), 'standings')
            ->select('standings.*')
            ->selectRaw('row_number() over (order by '.self::RANKING_ORDER.') as campaign_rank');

        return DB::query()->fromSub($ranked, 'participants');
    }
}
