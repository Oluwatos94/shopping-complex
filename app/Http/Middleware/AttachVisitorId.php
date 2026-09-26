<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use ModulesShoppingComplex\Billing\Services\ContactLinkService;
use Symfony\Component\HttpFoundation\Response;

class AttachVisitorId
{
    private const ONE_YEAR_MINUTES = 60 * 24 * 365;

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->cookie(ContactLinkService::VISITOR_COOKIE) === null) {
            $visitorId = (string) Str::uuid();
            $request->cookies->set(ContactLinkService::VISITOR_COOKIE, $visitorId);
            $request->attributes->set(ContactLinkService::FRESH_VISITOR_ATTRIBUTE, true);

            Cookie::queue(cookie(
                ContactLinkService::VISITOR_COOKIE,
                $visitorId,
                self::ONE_YEAR_MINUTES,
                secure: $request->isSecure(),
                httpOnly: true,
                sameSite: 'lax',
            ));
        }

        return $next($request);
    }
}
