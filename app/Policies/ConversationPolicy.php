<?php

declare(strict_types=1);

namespace App\Policies;

use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Models\Conversation;

class ConversationPolicy
{
    /**
     * Determine if the user can view the conversation.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->hasParticipant($user->id);
    }

    /**
     * Determine if the user can send messages in the conversation.
     */
    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $conversation->hasParticipant($user->id);
    }

    /**
     * Determine if the user can delete the conversation.
     */
    public function delete(User $user, Conversation $conversation): bool
    {
        // Only allow participants to delete (soft-delete their view of it)
        return $conversation->hasParticipant($user->id);
    }
}
