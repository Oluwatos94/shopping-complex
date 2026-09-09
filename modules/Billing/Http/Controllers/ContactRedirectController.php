<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use ModulesShoppingComplex\Billing\Data\ContactLinkPayload;
use ModulesShoppingComplex\Billing\Events\VendorContactClicked;
use ModulesShoppingComplex\Billing\Models\ContactClick;
use ModulesShoppingComplex\Billing\Services\ContactLinkService;
use ModulesShoppingComplex\Identity\Models\User;

class ContactRedirectController extends Controller
{
    public function __construct(
        private readonly ContactLinkService $links,
    ) {}

    public function __invoke(Request $request, string $token): RedirectResponse
    {
        abort_unless(URL::hasCorrectSignature($request), 404);

        $payload = $this->links->decode($token);
        abort_if($payload === null, 404);

        $vendor = User::find($payload->vendorId);
        abort_if($vendor === null, 404);

        $destination = $this->links->destinationFor($vendor, $payload->prefilledMessage);
        abort_if($destination === null, 404);

        $this->record($request, $payload, URL::signatureHasNotExpired($request));

        return redirect()->away($destination);
    }

    /**
     * Never let a billing failure stand between the buyer and the vendor.
     */
    private function record(Request $request, ContactLinkPayload $payload, bool $isBillable): void
    {
        try {
            $click = ContactClick::create([
                'vendor_id' => $payload->vendorId,
                'source' => $payload->source,
                'buyer_identity' => $payload->buyerIdentity,
                'is_billable' => $isBillable,
                'ip_address' => $request->ip(),
                'token_issued_at' => $payload->issuedAt,
                'created_at' => now(),
            ]);

            VendorContactClicked::dispatch($click);
        } catch (\Throwable $e) {
            Log::error('Contact click was not recorded', [
                'vendor_id' => $payload->vendorId,
                'source' => $payload->source->value,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
