<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Identity\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Session;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Identity\Repositories\ReferralRepository;
use RuntimeException;
use stdClass;

final readonly class ReferralService
{
    public const QUERY_PARAM = 'ref';

    public const SESSION_KEY = 'referral.pending_code';

    public const RECENT_LIMIT = 10;

    public const LEADERBOARD_LIMIT = 10;

    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    private const MAX_ATTEMPTS = 10;

    public function __construct(
        private ReferralRepository $referralRepository,
    ) {}

    public function codeFor(User $user): string
    {
        if ($user->referral_code !== null) {
            return $user->referral_code;
        }

        $code = $this->mintCode($user->id);

        $user->referral_code = $code;
        $user->syncOriginalAttribute('referral_code');

        return $code;
    }

    public function shareUrl(string $code): string
    {
        $path = (string) config('referral.share_path', '/register');

        return url($path).'?'.http_build_query([self::QUERY_PARAM => $code]);
    }

    public function attachPendingReferral(User $user): bool
    {
        $code = Session::pull(self::SESSION_KEY);

        return is_string($code) && $this->attachReferrer($user, $code);
    }

    public function attachReferrer(User $user, string $code): bool
    {
        $code = self::normalizeCode($code);

        if ($code === null) {
            return false;
        }

        $referrer = $this->referralRepository->findByCode($code);

        if ($referrer === null || $referrer->id === $user->id) {
            return false;
        }

        if (! $this->referralRepository->attachReferrer($user->id, $referrer->id)) {
            return false;
        }

        $user->referred_by = $referrer->id;
        $user->syncOriginalAttribute('referred_by');

        return true;
    }

    /**
     * Counted referrals, and how many joined in total. The gap between them is
     * the vendor's to close — those businesses signed up but have not listed.
     *
     * @return array{qualified: int, referred: int}
     */
    public function referralTallyFor(User $vendor): array
    {
        return $this->referralRepository->referralTally($vendor->id);
    }

    public function referralCountFor(User $vendor): int
    {
        return $this->referralTallyFor($vendor)['qualified'];
    }

    public function recentReferralsFor(User $vendor, int $limit = self::RECENT_LIMIT): array
    {
        return $this->referralRepository
            ->recentReferrals($vendor->id, $limit)
            ->map(fn (User $referral): array => [
                'name' => $referral->business_name ?? $referral->name,
                'email' => $referral->email,
                'joined_at' => $referral->created_at->toDateString(),
                'products_count' => (int) $referral->products_count,
            ])
            ->all();
    }

    public function totalParticipants(): int
    {
        return $this->referralRepository->countParticipants();
    }

    /**
     * @return list<array{rank: int, name: string, referral_count: int, is_you: bool}>
     */
    public function topReferrers(int $limit = self::LEADERBOARD_LIMIT, ?User $viewer = null): array
    {
        return $this->referralRepository
            ->topStandings($limit)
            ->map(fn (User $standing, int $position): array => [
                'rank' => $position + 1,
                'name' => $standing->business_name ?? $standing->name,
                'referral_count' => (int) $standing->qualified_referrals_count,
                'is_you' => $viewer !== null && $standing->id === $viewer->id,
            ])
            ->all();
    }

    public function rankFor(User $vendor): ?int
    {
        return $this->standingFor($vendor)['rank'] ?? null;
    }

    /**
     * The vendor's own row on the campaign board, or null if they never enrolled.
     * Rank and both counts come from the one query, so they cannot disagree.
     *
     * @return array{rank: int, referral_count: int, referred_count: int}|null
     */
    public function standingFor(User $vendor): ?array
    {
        $standing = $this->referralRepository->standingFor($vendor->id);

        return $standing === null ? null : [
            'rank' => (int) $standing->campaign_rank,
            'referral_count' => (int) $standing->qualified_referrals_count,
            'referred_count' => (int) $standing->referred_vendors_count,
        ];
    }

    public function participants(?string $search, int $perPage): LengthAwarePaginator
    {
        $participants = $this->referralRepository->paginateParticipants($search, $perPage);

        return new LengthAwarePaginator(
            $participants->getCollection()->map($this->toParticipantShape(...))->all(),
            $participants->total(),
            $participants->perPage(),
            $participants->currentPage(),
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }

    private function toParticipantShape(stdClass $participant): array
    {
        return [
            'user_id' => (int) $participant->id,
            'name' => (string) ($participant->business_name ?? $participant->name),
            'account_name' => (string) $participant->name,
            'email' => (string) $participant->email,
            'referral_count' => (int) $participant->qualified_referrals_count,
            'referred_count' => (int) $participant->referred_vendors_count,
            'rank' => (int) $participant->campaign_rank,
            'joined_at' => $participant->created_at === null
                ? null
                : Carbon::parse($participant->created_at)->toISOString(),
        ];
    }

    /**
     * @return array{total_participants: int, top: list<array{rank: int, name: string, referral_count: int, is_you: bool}>, my_rank: int|null, my_referral_count: int}
     */
    public function leaderboardFor(User $vendor, int $limit = self::LEADERBOARD_LIMIT): array
    {
        $top = $this->topReferrers($limit, $vendor);
        $mine = Arr::first($top, fn (array $entry): bool => $entry['is_you']) ?? $this->standingFor($vendor);

        return [
            'total_participants' => $this->totalParticipants(),
            'top' => $top,
            'my_rank' => $mine['rank'] ?? null,
            'my_referral_count' => $mine['referral_count'] ?? 0,
        ];
    }

    /**
     * Give every existing vendor a code so referral counts work from day one.
     *
     * @return int Number of vendors backfilled.
     */
    public function backfillVendorCodes(int $chunkSize = 500): int
    {
        $backfilled = 0;

        $this->referralRepository->chunkVendorsWithoutCode(
            $chunkSize,
            function (Collection $vendors) use (&$backfilled): void {
                foreach ($vendors as $vendor) {
                    $this->mintCode($vendor->id);
                    $backfilled++;
                }
            }
        );

        return $backfilled;
    }

    public static function normalizeCode(string $code): ?string
    {
        $code = strtoupper(trim($code));

        return preg_match('/^[A-Z0-9]{4,32}$/', $code) === 1 ? $code : null;
    }

    private function mintCode(int $userId): string
    {
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $code = $this->generateCode();

            try {
                if ($this->referralRepository->claimCode($userId, $code)) {
                    return $code;
                }
            } catch (UniqueConstraintViolationException) {
                continue;
            }

            $existing = $this->referralRepository->getCode($userId);

            if ($existing !== null) {
                return $existing;
            }
        }

        throw new RuntimeException("Unable to generate a unique referral code for user {$userId}.");
    }

    private function generateCode(): string
    {
        $length = max(4, (int) config('referral.code_length', 8));
        $lastIndex = strlen(self::ALPHABET) - 1;

        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= self::ALPHABET[random_int(0, $lastIndex)];
        }

        return $code;
    }
}
