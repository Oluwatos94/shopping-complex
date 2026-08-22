<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Identity\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Session;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Identity\Repositories\ReferralRepository;
use RuntimeException;

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

    public function referralCountFor(User $vendor): int
    {
        return $this->referralRepository->countReferrals($vendor->id);
    }

    /**
     * A breakdown for the vendor's own dashboard. Deliberately name and join
     * date only — a referrer has no claim to the email they referred.
     *
     * @return list<array{name: string, joined_at: string}>
     */
    public function recentReferralsFor(User $vendor, int $limit = self::RECENT_LIMIT): array
    {
        return $this->referralRepository
            ->recentReferrals($vendor->id, $limit)
            ->map(fn (User $referral): array => [
                'name' => $referral->name,
                'joined_at' => $referral->created_at->toDateString(),
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
                'referral_count' => (int) $standing->verified_referrals_count,
                'is_you' => $viewer !== null && $standing->id === $viewer->id,
            ])
            ->all();
    }

    public function rankFor(User $vendor): ?int
    {
        $standing = $this->referralRepository->standingFor($vendor->id);

        return $standing === null ? null : $this->rankOf($standing);
    }

    /**
     * @return array{total_participants: int, top: list<array{rank: int, name: string, referral_count: int, is_you: bool}>, my_rank: int|null, my_referral_count: int}
     */
    public function leaderboardFor(User $vendor, int $limit = self::LEADERBOARD_LIMIT): array
    {
        $top = $this->topReferrers($limit, $vendor);
        $mine = Arr::first($top, fn (array $entry): bool => $entry['is_you']) ?? $this->standingOutsideTop($vendor);

        return [
            'total_participants' => $this->totalParticipants(),
            'top' => $top,
            'my_rank' => $mine['rank'] ?? null,
            'my_referral_count' => $mine['referral_count'] ?? 0,
        ];
    }

    /**
     * @return array{rank: int, referral_count: int}|null
     */
    private function standingOutsideTop(User $vendor): ?array
    {
        $standing = $this->referralRepository->standingFor($vendor->id);

        return $standing === null ? null : [
            'rank' => $this->rankOf($standing),
            'referral_count' => (int) $standing->verified_referrals_count,
        ];
    }

    private function rankOf(User $standing): int
    {
        return $this->referralRepository->countStandingsAhead(
            $standing->id,
            (int) $standing->verified_referrals_count,
            (string) $standing->verified_referrals_max_created_at,
        ) + 1;
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

    /**
     * Persist a fresh unique code for a user that holds none. The conditional
     * write means a racing caller loses the update and re-reads the winner's
     * code instead of overwriting it.
     */
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
