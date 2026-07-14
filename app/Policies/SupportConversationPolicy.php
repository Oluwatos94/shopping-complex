<?php

declare(strict_types=1);

namespace App\Policies;

use ModulesShoppingComplex\Models\SupportConversation;
use ModulesShoppingComplex\Models\User;

class SupportConversationPolicy
{
    /**
     * Determine if the user can view (and message in) the support conversation.
     */
    public function view(User $user, SupportConversation $conversation): bool
    {
        return $conversation->user_id === $user->id;
    }
}
