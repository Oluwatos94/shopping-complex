<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Repositories;

use ModulesShoppingComplex\Models\Enums\WhatsAppSessionStateEnum;
use ModulesShoppingComplex\Models\WhatsAppSession;

class WhatsAppSessionRepository
{
    /**
     * Find a session by phone number or create a new idle one.
     * Updates last_active_at on every call.
     */
    public function findOrCreate(string $phoneNumber): WhatsAppSession
    {
        $session = WhatsAppSession::firstOrCreate(
            ['phone_number' => $phoneNumber],
            [
                'state' => WhatsAppSessionStateEnum::IDLE,
                'data' => null,
                'last_active_at' => now(),
            ]
        );

        $session->last_active_at = now();
        $session->save();

        return $session;
    }

    /**
     * Persist all changes on the session model.
     */
    public function save(WhatsAppSession $session): void
    {
        $session->save();
    }

    /**
     * Reset a session back to idle, clearing all state and data.
     */
    public function resetSession(WhatsAppSession $session): void
    {
        $session->state = WhatsAppSessionStateEnum::IDLE;
        $session->data = null;
        $session->last_active_at = now();
        $session->save();
    }
}
