<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Support\Policies;

use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Support\Models\SupportConversation;

class SupportConversationPolicy
{
    /**
     * Determine if the user can view (and message in) the support conversation.
     * Admins can view any conversation so agents can read the thread; guests
     * may only touch the guest conversation bound to their own session.
     */
    public function view(?User $user, SupportConversation $conversation): bool
    {
        if ($user !== null) {
            return $conversation->user_id === $user->id || $user->role === 'admin';
        }

        return $conversation->user_id === null
            && (int) session()->get(SupportConversation::GUEST_SESSION_KEY, 0) === $conversation->id;
    }

    /**
     * Determine if the user can escalate the conversation to a human agent.
     */
    public function escalate(User $user, SupportConversation $conversation): bool
    {
        return $conversation->user_id === $user->id;
    }

    /**
     * Determine if the user can mark the conversation as resolved.
     */
    public function resolve(User $user, SupportConversation $conversation): bool
    {
        return $conversation->user_id === $user->id || $user->role === 'admin';
    }

    /**
     * Determine if the user can reply as a human support agent.
     */
    public function actAsAgent(User $user, SupportConversation $conversation): bool
    {
        return $user->role === 'admin';
    }
}
