<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\URL;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Data\ContactLinkPayload;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\WhatsApp\Support\WhatsAppPhone;

/**
 * Mints the signed /c/{token} links that make a buyer's move to WhatsApp an
 * event we can observe, and therefore bill for. A raw wa.me link is invisible
 * to us: Meta does not report the click and it is not our domain.
 */
final class ContactLinkService
{
    public const TOKEN_TTL_DAYS = 30;

    public function urlFor(
        User $vendor,
        ViewSourceEnum $source,
        ?string $buyerIdentity = null,
        ?string $prefilledMessage = null,
    ): ?string {
        if ($this->vendorDigits($vendor) === null) {
            return null;
        }

        $payload = [
            'v' => $vendor->id,
            's' => $source->value,
            'i' => CarbonImmutable::now()->getTimestamp(),
        ];

        if ($buyerIdentity !== null && $buyerIdentity !== '') {
            $payload['b'] = $buyerIdentity;
        }

        if ($prefilledMessage !== null && $prefilledMessage !== '') {
            $payload['m'] = $prefilledMessage;
        }

        return URL::temporarySignedRoute(
            'contact.redirect',
            CarbonImmutable::now()->addDays(self::TOKEN_TTL_DAYS),
            ['token' => $this->encode($payload)],
        );
    }

    public function decode(string $token): ?ContactLinkPayload
    {
        $json = base64_decode($this->fromBase64Url($token), true);

        if ($json === false) {
            return null;
        }

        $payload = json_decode($json, true);

        if (! is_array($payload) || ! isset($payload['v'], $payload['s'])) {
            return null;
        }

        $source = ViewSourceEnum::tryFrom((string) $payload['s']);
        $vendorId = filter_var($payload['v'], FILTER_VALIDATE_INT);

        if ($source === null || $vendorId === false || $vendorId < 1) {
            return null;
        }

        return new ContactLinkPayload(
            vendorId: $vendorId,
            source: $source,
            buyerIdentity: isset($payload['b']) ? (string) $payload['b'] : null,
            prefilledMessage: isset($payload['m']) ? (string) $payload['m'] : null,
            issuedAt: isset($payload['i']) ? CarbonImmutable::createFromTimestamp((int) $payload['i']) : null,
        );
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

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encode(array $payload): string
    {
        return rtrim(strtr(base64_encode((string) json_encode($payload)), '+/', '-_'), '=');
    }

    private function fromBase64Url(string $token): string
    {
        $padding = strlen($token) % 4;

        return strtr($token, '-_', '+/').($padding === 0 ? '' : str_repeat('=', 4 - $padding));
    }
}
