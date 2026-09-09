<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Events\VendorContactClicked;
use ModulesShoppingComplex\Billing\Models\ContactClick;
use ModulesShoppingComplex\Billing\Models\ContactLink;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\WhatsApp\Support\WhatsAppPhone;

final class ContactLinkService
{
    public const TOKEN_TTL_DAYS = 30;

    private const TOKEN_LENGTH = 32;

    private const MAX_PREFILLED_MESSAGE = 500;

    private const REPEAT_CLICK_MINUTES = 60;

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

    public function recordClick(ContactLink $link, ?string $ipAddress = null): ?ContactClick
    {
        try {
            $click = DB::transaction(function () use ($link, $ipAddress): ContactClick {

                User::whereKey($link->vendor_id)->lockForUpdate()->first();

                return ContactClick::create([
                    'contact_link_id' => $link->id,
                    'vendor_id' => $link->vendor_id,
                    'source' => $link->source,
                    'buyer_identity' => $link->buyer_identity,
                    'is_billable' => ! $link->hasExpired() && ! $this->billedRecently($link, $ipAddress),
                    'ip_address' => $ipAddress,
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

    private function billedRecently(ContactLink $link, ?string $ipAddress): bool
    {
        $query = ContactClick::where('vendor_id', $link->vendor_id)
            ->where('is_billable', true)
            ->where('created_at', '>=', now()->subMinutes(self::REPEAT_CLICK_MINUTES));

        if ($link->buyer_identity !== null) {
            $query->where('buyer_identity', $link->buyer_identity);
        } elseif ($ipAddress !== null) {
            $query->whereNull('buyer_identity')->where('ip_address', $ipAddress);
        } else {
            return false;
        }

        return $query->exists();
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
