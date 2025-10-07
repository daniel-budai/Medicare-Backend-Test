<?php

namespace App\Policies;

use App\Models\FriendRequest;
use App\Models\User;

class FriendRequestPolicy
{
    /**
     * Determine if the user can accept the friend request.
     */
    public function accept(User $user, FriendRequest $friendRequest): bool
    {
        return $friendRequest->receiver_id === $user->id;
    }

    /**
     * Determine if the user can reject the friend request.
     */
    public function reject(User $user, FriendRequest $friendRequest): bool
    {
        return $friendRequest->receiver_id === $user->id;
    }
}

