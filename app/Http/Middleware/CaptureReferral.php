<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use ModulesShoppingComplex\Identity\Services\ReferralService;
use Symfony\Component\HttpFoundation\Response;

class CaptureReferral
{
    public function handle(Request $request, Closure $next): Response
    {
        $code = $request->query(ReferralService::QUERY_PARAM);

        if ($request->isMethod('GET') && is_string($code)) {
            $normalized = ReferralService::normalizeCode($code);

            if ($normalized !== null) {
                $request->session()->put(ReferralService::SESSION_KEY, $normalized);
            }
        }

        return $next($request);
    }
}
