<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\WhatsApp\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ModulesShoppingComplex\WhatsApp\Jobs\ProcessWhatsAppWebhook;

class WhatsAppController extends Controller
{
    /**
     * Verify the Meta webhook subscription.
     *
     * Meta sends a GET request with hub.verify_token, hub.challenge, and hub.mode.
     * Return the challenge as plain text to confirm ownership of the endpoint.
     */
    public function verify(Request $request): Response
    {
        $verifyToken = (string) $request->query('hub_verify_token', '');
        $challenge = (string) $request->query('hub_challenge', '');
        $mode = (string) $request->query('hub_mode', '');

        $configToken = trim((string) config('services.whatsapp.verify_token'), "\"'");

        if ($mode === 'subscribe' && $verifyToken === $configToken) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    public function receive(Request $request): Response
    {
        if (! $this->isValidSignature($request)) {
            return response('Forbidden', 403);
        }

        $payload = $request->all();

        if (($payload['object'] ?? '') === 'whatsapp_business_account') {
            ProcessWhatsAppWebhook::dispatch($payload);
        }

        return response('OK', 200);
    }

    private function isValidSignature(Request $request): bool
    {
        $appSecret = (string) config('services.whatsapp.app_secret');

        if ($appSecret === '') {
            return false;
        }

        $signature = (string) $request->header('X-Hub-Signature-256', '');
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expected, $signature);
    }
}
