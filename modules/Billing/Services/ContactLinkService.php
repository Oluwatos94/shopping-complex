<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Services;

use Illuminate\Support\Str;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Models\ContactLink;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\WhatsApp\Support\WhatsAppPhone;

/**
 * Mints the /c/{token} links that make a buyer's move to WhatsApp an event we
 * can observe, and therefore bill for. A raw wa.me link is invisible to us:
 * Meta does not report the click and it is not our domain.
 */
final class ContactLinkService
{
    public const TOKEN_TTL_DAYS = 30;

    private const TOKEN_LENGTH = 32;

    private const MAX_PREFILLED_MESSAGE = 500;

    public function mint(
        User $vendor,
        ViewSourceEnum $source,
        ?string $buyerIdentity = null,
        ?string $prefilledMessage = null,
    ): ?ContactLink {
        if ($this->vendorDigits($vendor) === null) {
            return null;
        }

        return ContactLink::create([
            'token' => Str::random(self::TOKEN_LENGTH),
            'vendor_id' => $vendor->id,
            'source' => $source,
            'buyer_identity' => $this->trimToNull($buyerIdentity, 64),
            'prefilled_message' => $this->trimToNull($prefilledMessage, self::MAX_PREFILLED_MESSAGE),
            'expires_at' => now()->addDays(self::TOKEN_TTL_DAYS),
            'created_at' => now(),
        ]);
    }

    public function urlFor(
        User $vendor,
        ViewSourceEnum $source,
        ?string $buyerIdentity = null,
        ?string $prefilledMessage = null,
    ): ?string {
        $link = $this->mint($vendor, $source, $buyerIdentity, $prefilledMessage);

        return $link === null ? null : route('contact.redirect', ['token' => $link->token]);
    }

    public function resolve(string $token): ?ContactLink
    {
        return ContactLink::where('token', $token)->first();
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
