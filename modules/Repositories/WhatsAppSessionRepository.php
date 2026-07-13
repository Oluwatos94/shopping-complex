<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Repositories;

use ModulesShoppingComplex\Models\Enums\WhatsAppSessionStateEnum;
use ModulesShoppingComplex\Models\WhatsAppSession;

class WhatsAppSessionRepository
{
    public function findOrCreate(string $phoneNumber): WhatsAppSession
    {
        return WhatsAppSession::firstOrCreate(
            ['phone_number' => $phoneNumber],
            [
                'state' => WhatsAppSessionStateEnum::IDLE,
                'data' => null,
                'last_active_at' => now(),
            ]
        );
    }

    /**
     * Mark the session as active now.
     */
    public function touch(WhatsAppSession $session): void
    {
        $session->last_active_at = now();
        $session->save();
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
