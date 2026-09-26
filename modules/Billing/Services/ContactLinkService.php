<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Events\VendorContactClicked;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Billing\Models\ContactClick;
use ModulesShoppingComplex\Billing\Models\ContactLink;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\WhatsApp\Support\WhatsAppPhone;

final class ContactLinkService
{
    public const TOKEN_TTL_DAYS = 30;

    public const VISITOR_COOKIE = 'visitor_id';

    public const FRESH_VISITOR_ATTRIBUTE = 'visitor_id_fresh';

    private const TOKEN_LENGTH = 32;

    private const MAX_PREFILLED_MESSAGE = 500;

    private const ANONYMOUS_REPEAT_MINUTES = 60;

    public function mint(
        User $vendor,
        ViewSourceEnum $source,
        ?string $buyerIdentity = null,
        ?string $prefilledMessage = null,
    ): ?ContactLink {
        if ($this->vendorDigits($vendor) === null) {
            return null;
        }

        try {
            return ContactLink::create([
                'token' => Str::random(self::TOKEN_LENGTH),
                'vendor_id' => $vendor->id,
                'source' => $source,
                'buyer_identity' => $this->trimToNull($buyerIdentity, 64),
                'prefilled_message' => $this->trimToNull($prefilledMessage, self::MAX_PREFILLED_MESSAGE),
                'expires_at' => now()->addDays(self::TOKEN_TTL_DAYS),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Contact link could not be minted', [
                'vendor_id' => $vendor->id,
                'source' => $source->value,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function urlFor(
        User $vendor,
        ViewSourceEnum $source,
        ?string $buyerIdentity = null,
        ?string $prefilledMessage = null,
    ): ?string {
        if ($this->vendorDigits($vendor) === null) {
            return null;
        }

        $link = $this->mint($vendor, $source, $buyerIdentity, $prefilledMessage);

        return $link === null
            ? $this->destinationFor($vendor, $prefilledMessage)
            : route('contact.redirect', ['token' => $link->token]);
    }

    public function resolve(string $token): ?ContactLink
    {
        return ContactLink::where('token', $token)->first();
    }

    public function resolveBuyerIdentity(Request $request): string
    {
        $userId = Auth::id();

        if ($userId !== null) {
            return 'user_'.$userId;
        }

        $visitorId = (string) $request->cookie(self::VISITOR_COOKIE);

        if ($visitorId !== '' && ! $request->attributes->get(self::FRESH_VISITOR_ATTRIBUTE, false)) {
            return 'visitor_'.$visitorId;
        }

        return 'anon_'.substr(hash('sha256', $request->ip().'|'.(string) $request->userAgent()), 0, 40);
    }

    public function issuedVisitorId(Request $request): ?string
    {
        if (Auth::check() || ! $request->attributes->get(self::FRESH_VISITOR_ATTRIBUTE, false)) {
            return null;
        }

        return $this->trimToNull((string) $request->cookie(self::VISITOR_COOKIE), 64);
    }

    /**
     * Re-key the anonymous leads and clicks first recorded when this cookie was
     * issued. Only touches anon_ rows, so a claimed lead is never taken twice.
     */
    public function claimIssuedVisitor(string $visitorId, string $identity): void
    {
        $clicks = ContactClick::where('issued_visitor_id', $visitorId)->where('buyer_identity', 'like', 'anon_%');

        BillableLead::whereIn('contact_click_id', (clone $clicks)->select('id'))
            ->where('buyer_identity', 'like', 'anon_%')
            ->update(['buyer_identity' => $identity]);

        $clicks->update(['buyer_identity' => $identity]);
    }

    public function mergeVisitorIntoAccount(string $visitorId, int $userId): void
    {
        if ($visitorId === '') {
            return;
        }

        $from = 'visitor_'.$visitorId;
        $to = 'user_'.$userId;

        try {
            DB::transaction(function () use ($visitorId, $from, $to): void {
                $this->claimIssuedVisitor($visitorId, $to);
                BillableLead::where('buyer_identity', $from)->update(['buyer_identity' => $to]);
                ContactClick::where('buyer_identity', $from)->update(['buyer_identity' => $to]);
            });
        } catch (\Throwable $e) {
            Log::warning('Visitor identity merge failed', [
                'visitor_id' => $visitorId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function recordClick(
        ContactLink $link,
        ?string $ipAddress = null,
        ?string $buyerIdentity = null,
        ?string $userAgent = null,
        ?string $issuedVisitorId = null,
    ): ?ContactClick {
        $identity = $buyerIdentity ?? $link->buyer_identity;

        try {
            $click = DB::transaction(function () use ($link, $ipAddress, $identity, $userAgent, $issuedVisitorId): ContactClick {

                User::whereKey($link->vendor_id)->lockForUpdate()->first();

                return ContactClick::create([
                    'contact_link_id' => $link->id,
                    'vendor_id' => $link->vendor_id,
                    'source' => $link->source,
                    'buyer_identity' => $identity,
                    'issued_visitor_id' => $issuedVisitorId,
                    'is_billable' => ! $link->hasExpired() && ! $this->anonymousRepeat($identity, $link->vendor_id, $ipAddress),
                    'ip_address' => $ipAddress,
                    'user_agent' => $this->trimToNull($userAgent, 255),
                    'created_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Contact click was not recorded', [
                'contact_link_id' => $link->id,
                'vendor_id' => $link->vendor_id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        VendorContactClicked::dispatch($click);

        return $click;
    }

    /**
     * Identified buyers are deduplicated by the lead ledger. Anonymous web
     * buyers have no identity to key on yet, so they get a short IP window.
     */
    private function anonymousRepeat(?string $identity, int $vendorId, ?string $ipAddress): bool
    {
        if ($identity !== null || $ipAddress === null) {
            return false;
        }

        return ContactClick::where('vendor_id', $vendorId)
            ->whereNull('buyer_identity')
            ->where('ip_address', $ipAddress)
            ->where('is_billable', true)
            ->where('created_at', '>=', now()->subMinutes(self::ANONYMOUS_REPEAT_MINUTES))
            ->exists();
    }

    public function destinationFor(User $vendor, ?string $prefilledMessage = null): ?string
    {
        $digits = $this->vendorDigits($vendor);

        if ($digits === null) {
            return null;
        }

        $url = 'https://wa.me/'.$digits;

        if ($prefilledMessage !== null && $prefilledMessage !== '') {
            $url .= '?text='.rawurlencode($prefilledMessage);
        }

        return $url;
    }

    private function vendorDigits(User $vendor): ?string
    {
        $number = (string) ($vendor->whatsapp_number ?? '');

        return $number === '' ? null : WhatsAppPhone::toE164($number);
    }

    private function trimToNull(?string $value, int $limit): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $limit);
    }
}
