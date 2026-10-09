<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Contracts\LeadDebitor;
use ModulesShoppingComplex\Billing\Data\LeadAcceptResult;
use ModulesShoppingComplex\Billing\Data\LeadRequestResult;
use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
use ModulesShoppingComplex\Billing\Enums\LeadAcceptStatusEnum;
use ModulesShoppingComplex\Billing\Enums\LeadRequestStatusEnum;
use ModulesShoppingComplex\Billing\Events\VendorLeadCharged;
use ModulesShoppingComplex\Billing\Events\VendorLeadDeclined;
use ModulesShoppingComplex\Billing\Events\VendorLeadExpired;
use ModulesShoppingComplex\Billing\Events\VendorLeadRequested;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\WhatsApp\Contracts\WhatsAppSender;
use ModulesShoppingComplex\WhatsApp\Support\WhatsAppPhone;

/**
 * Accept-to-charge leads.
 *
 * The click model billed a vendor the moment someone tapped a contact link, which
 * the vendor could not control and the platform could not verify. Here a buyer only
 * *requests* an introduction; the vendor is charged when they accept it:
 *
 *   buyer asks (bot tool or web "Ref:" message)  -> request()  -> PENDING, nothing charged
 *   vendor taps Accept (WhatsApp or Leads page)  -> accept()   -> CHARGED, contacts exchanged
 *   vendor taps Decline                          -> decline()  -> DECLINED, nothing charged
 *   nobody answers before expires_at             -> expireOverdue() -> EXPIRED, nothing charged
 *
 * The buyer is always identified by the WhatsApp number Meta delivered their message
 * from, so identities cannot be forged, and the vendor's number is only revealed after
 * the vendor has paid.
 */
final class LeadAcceptanceService
{
    public const ACCEPT_PAYLOAD = 'LEAD_ACCEPT';

    public const DECLINE_PAYLOAD = 'LEAD_DECLINE';

    private const PAYLOAD_PATTERN = '/^(LEAD_ACCEPT|LEAD_DECLINE):(\d+)$/';

    private const REFERENCE_PATTERN = '/\bRef:\s*([A-Za-z0-9]{10,32})\b/i';

    public function __construct(
        private readonly LeadDebitor $debitor,
        private readonly LeadPricingService $pricing,
        private readonly CoinWalletService $wallet,
        private readonly ContactLinkService $links,
        private readonly LeadBuyerContextResolver $context,
        private readonly WhatsAppSender $whatsApp,
    ) {}

    public static function isEnabled(): bool
    {
        return config('billing.leads.mode') === 'accept';
    }

    /**
     * A buyer asks to be introduced to a vendor.
     */
    public function request(User $vendor, string $buyerPhone, ViewSourceEnum $channel): LeadRequestResult
    {
        $buyer = (string) preg_replace('/\D/', '', $buyerPhone);
        $vendorNumber = WhatsAppPhone::toE164((string) ($vendor->whatsapp_number ?? ''));

        if ($vendorNumber === null) {
            return new LeadRequestResult(LeadRequestStatusEnum::NO_CONTACT, vendor: $vendor);
        }

        if ($buyer === '' || WhatsAppPhone::toE164($buyer) === $vendorNumber) {
            return new LeadRequestResult(LeadRequestStatusEnum::SELF, vendor: $vendor);
        }

        $result = DB::transaction(function () use ($vendor, $buyer, $channel, $vendorNumber): LeadRequestResult {
            User::whereKey($vendor->id)->lockForUpdate()->first();

            $latest = BillableLead::where('buyer_identity', $buyer)
                ->where('vendor_id', $vendor->id)
                ->where('window_start', '>', now()->subDays(LeadBillingService::WINDOW_DAYS))
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if ($latest !== null) {
                $existing = $this->resolveExisting($latest);

                if ($existing !== null) {
                    return new LeadRequestResult($existing, $latest, $vendor);
                }
            }

            $open = BillableLead::where('buyer_identity', $buyer)->awaitingVendor()->count();

            if ($open >= max(1, (int) config('billing.leads.max_pending_per_buyer', 5))) {
                return new LeadRequestResult(LeadRequestStatusEnum::TOO_MANY_PENDING, vendor: $vendor);
            }

            $lead = BillableLead::create([
                'vendor_id' => $vendor->id,
                'buyer_identity' => $buyer,
                'channel' => $channel,
                'coins_charged' => 0,
                'state' => BillableLeadStateEnum::PENDING,
                'delivered_number' => $vendorNumber,
                'window_start' => now(),
                'repeat_count' => 0,
                'last_click_at' => now(),
                'expires_at' => now()->addHours(max(1, (int) config('billing.leads.accept_window_hours', 3))),
            ]);

            return new LeadRequestResult(LeadRequestStatusEnum::REQUESTED, $lead, $vendor);
        });

        if ($result->status === LeadRequestStatusEnum::REQUESTED && $result->lead !== null) {
            // Outside the transaction: this may reverse-geocode over HTTP.
            $this->context->resolveAndStore($result->lead);
            VendorLeadRequested::dispatch($result->lead);
        }

        return $result;
    }

    /**
     * A buyer's message to the platform number carrying "Ref: <token>" from a web
     * contact button. Null when the message has no reference at all.
     */
    public function requestByReference(string $text, string $buyerPhone): ?LeadRequestResult
    {
        $reference = self::referenceIn($text);

        if ($reference === null) {
            return null;
        }

        $link = $this->links->resolve($reference);
        $vendor = $link?->vendor;

        if ($link === null || $vendor === null) {
            return new LeadRequestResult(LeadRequestStatusEnum::UNKNOWN_REFERENCE);
        }

        return $this->request($vendor, $buyerPhone, $link->source);
    }

    public static function referenceIn(string $text): ?string
    {
        return preg_match(self::REFERENCE_PATTERN, $text, $matches) === 1 ? $matches[1] : null;
    }

    /**
     * The vendor accepts: charge them and, on success, fire VendorLeadCharged so both
     * sides receive each other's contact.
     */
    public function accept(BillableLead $lead): LeadAcceptResult
    {
        $expiredNow = false;

        $result = DB::transaction(function () use ($lead, &$expiredNow): LeadAcceptResult {
            $vendor = User::whereKey($lead->vendor_id)->lockForUpdate()->firstOrFail();
            $lead = BillableLead::whereKey($lead->id)->lockForUpdate()->firstOrFail();

            if ($lead->state === BillableLeadStateEnum::CHARGED) {
                return new LeadAcceptResult(LeadAcceptStatusEnum::ALREADY_ACCEPTED, $lead, $lead->coins_charged, $this->wallet->balance($vendor));
            }

            if ($lead->state !== BillableLeadStateEnum::PENDING) {
                return new LeadAcceptResult(LeadAcceptStatusEnum::CLOSED, $lead);
            }

            if ($lead->expires_at !== null && $lead->expires_at->isPast()) {
                $lead->forceFill(['state' => BillableLeadStateEnum::EXPIRED])->save();
                $expiredNow = true;

                return new LeadAcceptResult(LeadAcceptStatusEnum::CLOSED, $lead);
            }

            $cost = $this->pricing->costFor($vendor);
            $cap = $vendor->daily_coin_cap;

            if ($cap !== null && $this->chargedToday($vendor) + $cost > $cap) {
                return new LeadAcceptResult(LeadAcceptStatusEnum::DAILY_CAP, $lead, $cost, $this->wallet->balance($vendor));
            }

            if ($this->debitor->debit($vendor, $lead, $cost) === 0) {
                return new LeadAcceptResult(LeadAcceptStatusEnum::INSUFFICIENT_BALANCE, $lead, $cost, $this->wallet->balance($vendor));
            }

            $lead->forceFill([
                'state' => BillableLeadStateEnum::CHARGED,
                'coins_charged' => $cost,
                'unbilled_reason' => null,
                'delivered_number' => WhatsAppPhone::toE164((string) ($vendor->whatsapp_number ?? '')) ?? $lead->delivered_number,
                'accepted_at' => now(),
                'responded_at' => now(),
            ])->save();

            return new LeadAcceptResult(LeadAcceptStatusEnum::ACCEPTED, $lead, $cost, $this->wallet->balance($vendor));
        });

        if ($result->status === LeadAcceptStatusEnum::ACCEPTED) {
            VendorLeadCharged::dispatch($result->lead);
        }

        if ($expiredNow) {
            VendorLeadExpired::dispatch($result->lead);
        }

        return $result;
    }

    /**
     * The vendor turns the request down. Returns false when it was no longer pending.
     */
    public function decline(BillableLead $lead): bool
    {
        $declined = DB::transaction(function () use ($lead): ?BillableLead {
            $lead = BillableLead::whereKey($lead->id)->lockForUpdate()->firstOrFail();

            if ($lead->state !== BillableLeadStateEnum::PENDING) {
                return null;
            }

            $lead->forceFill([
                'state' => BillableLeadStateEnum::DECLINED,
                'responded_at' => now(),
            ])->save();

            return $lead;
        });

        if ($declined === null) {
            return false;
        }

        VendorLeadDeclined::dispatch($declined);

        return true;
    }

    /**
     * Close every request the vendor did not answer in time. Returns how many expired.
     */
    public function expireOverdue(): int
    {
        $expired = 0;

        BillableLead::where('state', BillableLeadStateEnum::PENDING)
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(200, function ($leads) use (&$expired): void {
                foreach ($leads as $lead) {
                    // Conditional update: skip any lead accepted/declined since we read it.
                    $changed = BillableLead::whereKey($lead->id)
                        ->where('state', BillableLeadStateEnum::PENDING)
                        ->update(['state' => BillableLeadStateEnum::EXPIRED->value, 'updated_at' => now()]);

                    if ($changed === 1) {
                        $expired++;
                        VendorLeadExpired::dispatch($lead->refresh());
                    }
                }
            });

        return $expired;
    }

    /**
     * Handle a vendor's tap on the Accept / Decline quick-reply buttons of the
     * `lead_request` template. Returns false when the payload is not ours, so the
     * caller can hand the message to the buyer bot instead.
     */
    public function respondFromWhatsApp(string $from, string $payload): bool
    {
        if (preg_match(self::PAYLOAD_PATTERN, trim($payload), $matches) !== 1) {
            return false;
        }

        $lead = BillableLead::find((int) $matches[2]);
        $vendor = $lead?->vendor;
        $vendorNumber = $vendor === null ? null : WhatsAppPhone::toE164((string) ($vendor->whatsapp_number ?? ''));

        // Only the vendor's own WhatsApp number may answer for them.
        if ($lead === null || $vendorNumber === null || $vendorNumber !== WhatsAppPhone::toE164($from)) {
            Log::warning('Lead response from an unexpected number', [
                'lead_id' => (int) $matches[2],
                'from' => '***'.substr($from, -4),
            ]);
            $this->whatsApp->sendText($from, 'We could not find that lead request.');

            return true;
        }

        if ($matches[1] === self::DECLINE_PAYLOAD) {
            $this->whatsApp->sendText($from, $this->decline($lead)
                ? 'Request declined. No coins were charged.'
                : 'This request is already closed. No coins were charged.');

            return true;
        }

        $this->whatsApp->sendText($from, $this->vendorReplyFor($this->accept($lead)));

        return true;
    }

    /**
     * The buyer's contact as shown to a vendor who has paid for the lead.
     *
     * @return array{phone: string, whatsapp_url: string}|null
     */
    public function buyerContactFor(BillableLead $lead): ?array
    {
        if (! $lead->wasAccepted() || $lead->state !== BillableLeadStateEnum::CHARGED) {
            return null;
        }

        $digits = (string) preg_replace('/\D/', '', $lead->buyer_identity);

        return $digits === '' ? null : ['phone' => '+'.$digits, 'whatsapp_url' => 'https://wa.me/'.$digits];
    }

    private function vendorReplyFor(LeadAcceptResult $result): string
    {
        $lead = $result->lead;

        return match ($result->status) {
            LeadAcceptStatusEnum::ACCEPTED, LeadAcceptStatusEnum::ALREADY_ACCEPTED => $this->acceptedMessage($result),
            LeadAcceptStatusEnum::CLOSED => 'This request has expired or was already declined. No coins were charged.',
            LeadAcceptStatusEnum::INSUFFICIENT_BALANCE => sprintf(
                "You need %d coins to accept this lead but have %d.\nTop up here: %s\nThe request stays open until %s.",
                $result->cost,
                $result->balance,
                route('vendor.coins.packs'),
                $lead->expires_at?->format('g:ia') ?? 'it expires',
            ),
            LeadAcceptStatusEnum::DAILY_CAP => sprintf(
                "Accepting this lead (%d coins) would go over your daily coin limit.\nYou can raise the limit on your dashboard: %s",
                $result->cost,
                route('vendor.dashboard'),
            ),
        };
    }

    private function acceptedMessage(LeadAcceptResult $result): string
    {
        $contact = $this->buyerContactFor($result->lead);
        $search = $result->lead->buyer_search !== null ? " looking for \"{$result->lead->buyer_search}\"" : '';

        $lines = $result->status === LeadAcceptStatusEnum::ACCEPTED
            ? [sprintf('✅ Lead accepted. %d coins charged, balance %d.', $result->cost, $result->balance)]
            : ['You already accepted this lead. No extra coins were charged.'];

        if ($contact !== null) {
            $lines[] = "Chat with the buyer{$search}: {$contact['whatsapp_url']}";
        }

        if ($result->status === LeadAcceptStatusEnum::ACCEPTED) {
            $lines[] = 'We have also sent them your WhatsApp link.';
        }

        return implode("\n", $lines);
    }

    /**
     * What an existing lead inside the 30-day window means for a new request, or null
     * when a fresh request should be opened (expired, legacy unbilled, overdue pending).
     */
    private function resolveExisting(BillableLead $latest): ?LeadRequestStatusEnum
    {
        switch ($latest->state) {
            case BillableLeadStateEnum::CHARGED:
                $latest->forceFill([
                    'repeat_count' => $latest->repeat_count + 1,
                    'last_click_at' => now(),
                ])->save();

                return LeadRequestStatusEnum::ALREADY_ACCEPTED;

            case BillableLeadStateEnum::DECLINED:
                return LeadRequestStatusEnum::DECLINED;

            case BillableLeadStateEnum::PENDING:
                if ($latest->expires_at === null || $latest->expires_at->isFuture()) {
                    return LeadRequestStatusEnum::ALREADY_PENDING;
                }

                // Overdue but not yet swept by the scheduler: close it quietly, the
                // buyer is asking again right now.
                $latest->forceFill(['state' => BillableLeadStateEnum::EXPIRED])->save();

                return null;

            default:
                return null;
        }
    }

    private function chargedToday(User $vendor): int
    {
        return (int) BillableLead::where('vendor_id', $vendor->id)
            ->where('state', BillableLeadStateEnum::CHARGED)
            ->where(fn ($query) => $query
                ->where('accepted_at', '>=', now()->startOfDay())
                ->orWhere(fn ($legacy) => $legacy->whereNull('accepted_at')->where('created_at', '>=', now()->startOfDay())))
            ->sum('coins_charged');
    }
}
