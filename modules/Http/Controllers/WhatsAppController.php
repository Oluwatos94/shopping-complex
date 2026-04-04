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

        if ($mode === 'subscribe' && $verifyToken === config('services.whatsapp.verify_token')) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    /**
     * Receive an incoming WhatsApp message from Meta.
     *
     * Must respond 200 immediately — Meta retries if the response is slow.
     * All processing is handed off to a background job.
     */
    public function receive(Request $request): Response
    {
        $payload = $request->all();

        if (($payload['object'] ?? '') === 'whatsapp_business_account') {
            ProcessWhatsAppWebhook::dispatch($payload);
        }

        return response('OK', 200);
    }
}
