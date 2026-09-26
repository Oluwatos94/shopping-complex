<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Services\ContactLinkService;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Shared\Support\CrawlerDetector;

class ContactRedirectController extends Controller
{
    public function __construct(
        private readonly ContactLinkService $links,
    ) {}

    public function issue(Request $request, string $vendorSlug): RedirectResponse
    {
        $vendor = User::where('slug', $vendorSlug)->where('role', 'vendor')->first();
        abort_if($vendor === null, 404);

        $message = is_string($request->query('message')) ? $request->query('message') : null;

        $url = CrawlerDetector::isCrawler($request->userAgent())
            ? $this->links->destinationFor($vendor, $message)
            : $this->links->urlFor($vendor, ViewSourceEnum::WEB, null, $message);

        abort_if($url === null, 404);

        return redirect($url);
    }

    public function __invoke(Request $request, string $token): RedirectResponse
    {
        $link = $this->links->resolve($token);
        abort_if($link === null, 404);

        $vendor = $link->vendor;
        abort_if($vendor === null, 404);

        $destination = $this->links->destinationFor($vendor, $link->prefilled_message);
        abort_if($destination === null, 404);

        $identity = $link->buyer_identity ?? $this->links->resolveBuyerIdentity($request);

        if (
            ! $request->isMethod('HEAD')
            && ! CrawlerDetector::isCrawler($request->userAgent())
            && $this->withinRateLimit($vendor->id, $identity)
        ) {
            $this->links->recordClick(
                $link,
                $request->ip(),
                $identity,
                $request->userAgent(),
                $link->buyer_identity === null ? $this->links->issuedVisitorId($request) : null,
            );
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
