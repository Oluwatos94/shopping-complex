<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ModulesShoppingComplex\Jobs\ProcessWhatsAppWebhook;

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

    /**
     * Receive an incoming WhatsApp message from Meta.
     *
     * Validates the X-Hub-Signature-256 HMAC before processing.
     * Must respond 200 immediately — Meta retries if the response is slow.
     * All processing is handed off to a background job.
     */
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

    /**
     * Verify Meta's X-Hub-Signature-256 HMAC signature.
     *
     * Meta signs each webhook delivery with HMAC-SHA256 using the app secret.
     * Without this check, any actor can forge requests to this endpoint.
     */
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
