<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use ModulesShoppingComplex\Billing\Services\ContactLinkService;

class ContactRedirectController extends Controller
{
    public function __construct(
        private readonly ContactLinkService $links,
    ) {}

    public function __invoke(Request $request, string $token): RedirectResponse
    {
        $link = $this->links->resolve($token);
        abort_if($link === null, 404);

        $vendor = $link->vendor;
        abort_if($vendor === null, 404);

        $destination = $this->links->destinationFor($vendor, $link->prefilled_message);
        abort_if($destination === null, 404);

        if (! $request->isMethod('HEAD') && $this->withinRateLimit($vendor->id, $link->buyer_identity ?? (string) $request->ip())) {
            $this->links->recordClick($link, $request->ip());
        }

        return redirect()->away($destination);
    }

    private function withinRateLimit(int $vendorId, string $identity): bool
    {
        $key = 'contact-redirect:'.$vendorId.':'.$identity;
        $max = (int) config('billing.guards.rate_limit.max', 5);
        $seconds = (int) config('billing.guards.rate_limit.seconds', 60);

        return RateLimiter::hit($key, $seconds) <= $max;
    }
}
