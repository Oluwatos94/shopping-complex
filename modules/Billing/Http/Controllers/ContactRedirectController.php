<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use ModulesShoppingComplex\Billing\Events\VendorContactClicked;
use ModulesShoppingComplex\Billing\Models\ContactClick;
use ModulesShoppingComplex\Billing\Models\ContactLink;
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

        // A HEAD request is a link preview or a scanner, never a buyer.
        if (! $request->isMethod('HEAD')) {
            $this->record($request, $link);
        }

        return redirect()->away($destination);
    }

    /**
     * Never let a billing failure stand between the buyer and the vendor.
     */
    private function record(Request $request, ContactLink $link): void
    {
        try {
            $click = ContactClick::create([
                'contact_link_id' => $link->id,
                'vendor_id' => $link->vendor_id,
                'source' => $link->source,
                'buyer_identity' => $link->buyer_identity,
                'is_billable' => ! $link->hasExpired(),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            VendorContactClicked::dispatch($click);
        } catch (\Throwable $e) {
            Log::error('Contact click was not recorded', [
                'contact_link_id' => $link->id,
                'vendor_id' => $link->vendor_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
