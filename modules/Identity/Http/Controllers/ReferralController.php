<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Identity\Services\ReferralService;

class ReferralController extends Controller
{
    public function __construct(
        private readonly ReferralService $referralService,
    ) {}

    public function show(): JsonResponse
    {
        $user = $this->vendor();

        $code = $this->referralService->codeFor($user);
        $tally = $this->referralService->referralTallyFor($user);

        return response()->json([
            'code' => $code,
            'share_url' => $this->referralService->shareUrl($code),
            'count' => $tally['qualified'],
            'referred_count' => $tally['referred'],
            'min_products' => User::minReferralProducts(),
            'recent' => $this->referralService->recentReferralsFor($user),
        ]);
    }

    public function leaderboard(): Response
    {
        return Inertia::render(
            'Vendor/ReferralLeaderboard',
            $this->referralService->leaderboardFor($this->vendor())
        );
    }

    private function vendor(): User
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->role !== 'vendor') {
            abort(403, 'Only vendors can access the referral programme.');
        }

        return $user;
    }
}
