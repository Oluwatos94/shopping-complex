<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Services\ContactLinkService;
use ModulesShoppingComplex\Billing\Services\LeadAcceptanceService;
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

        if (LeadAcceptanceService::isEnabled()) {
            return $this->requestThroughPlatform($request, $vendor, $message);
        }

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

        // Accept mode: old bot links are no longer a direct line to the vendor (that
        // was the chargeable click). Send the buyer to the platform chat instead.
        if (LeadAcceptanceService::isEnabled()) {
            $platform = $this->links->platformChatUrl($link);

            return $platform === null
                ? redirect()->route('vendor.show', $vendor->slug)
                : redirect()->away($platform);
        }

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

    /**
     * Accept mode: the buyer is sent to a chat with Jiidaa's own number, prefilled with
     * a reference to this vendor. The bot turns that message into a lead request for the
     * vendor to accept, so nothing is charged and no vendor number is revealed here.
     */
    private function requestThroughPlatform(Request $request, User $vendor, ?string $message): RedirectResponse
    {
        if (CrawlerDetector::isCrawler($request->userAgent())) {
            return redirect()->route('vendor.show', $vendor->slug);
        }

        $url = $this->links->platformRequestUrl($vendor, ViewSourceEnum::WEB, $message);

        if ($url === null) {
            return redirect()->route('vendor.show', $vendor->slug)
                ->with('error', 'This vendor cannot be contacted right now. Please try again later.');
        }

        return redirect()->away($url);
    }

    private function withinRateLimit(int $vendorId, string $identity): bool
    {
        $key = 'contact-redirect:'.$vendorId.':'.$identity;
        $max = (int) config('billing.guards.rate_limit.max', 5);
        $seconds = (int) config('billing.guards.rate_limit.seconds', 60);

        return RateLimiter::hit($key, $seconds) <= $max;
    }
}
