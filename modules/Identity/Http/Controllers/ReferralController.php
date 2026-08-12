<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Identity\Services\ReferralService;

class ReferralController extends Controller
{
    public function __construct(
        private readonly ReferralService $referralService,
    ) {}

    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->role !== 'vendor') {
            abort(403, 'Only vendors can access referral codes.');
        }

        $code = $this->referralService->codeFor($user);

        return response()->json([
            'code' => $code,
            'share_url' => $this->referralService->shareUrl($code),
            'count' => $this->referralService->referralCountFor($user),
            'recent' => $this->referralService->recentReferralsFor($user),
        ]);
    }
}
