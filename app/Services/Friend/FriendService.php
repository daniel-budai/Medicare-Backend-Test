<?php

namespace App\Services\Friend;

use App\Enums\FriendRequestStatus;
use App\Models\FriendRequest;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FriendService
{
    /**
     * Send a friend request.
     *
     * @param User $sender
     * @param int $receiverId
     * @return FriendRequest
     */
    public function sendFriendRequest(User $sender, int $receiverId): FriendRequest
    {
        // Check if receiver exists and is active
        $receiver = User::active()->find($receiverId);
        abort_unless(
            $receiver !== null,
            404,
            'User not found.'
        );

        // Check if users are already friends
        abort_if(
            $this->areFriends($sender->id, $receiverId),
            409,
            'Users are already friends.'
        );

        // Check if there's already a pending request
        $existingRequest = FriendRequest::where(function ($query) use ($sender, $receiverId) {
            $query->where('sender_id', $sender->id)
                ->where('receiver_id', $receiverId);
        })->orWhere(function ($query) use ($sender, $receiverId) {
            $query->where('sender_id', $receiverId)
                ->where('receiver_id', $sender->id);
        })->where('status', FriendRequestStatus::PENDING)
            ->first();

        abort_if(
            $existingRequest !== null,
            409,
            'A pending friend request already exists with this user.'
        );

        $friendRequest = FriendRequest::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiverId,
            'status' => FriendRequestStatus::PENDING,
        ]);

        // Return with relationships loaded
        return $friendRequest->load(['sender', 'receiver']);
    }

    /**
     * Get pending friend requests for a user.
     *
     * @param User $user
     * @return Collection
     */
    public function getPendingRequests(User $user): Collection
    {
        return FriendRequest::with(['sender', 'receiver'])
            ->where('receiver_id', $user->id)
            ->where('status', FriendRequestStatus::PENDING)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Accept a friend request.
     *
     * @param FriendRequest $friendRequest
     * @return void
     */
    public function acceptFriendRequest(FriendRequest $friendRequest): void
    {
        DB::transaction(function () use ($friendRequest) {
            // Update friend request status
            $friendRequest->update(['status' => FriendRequestStatus::ACCEPTED]);

            // Create mutual friendships
            Friendship::create([
                'user_id' => $friendRequest->sender_id,
                'friend_id' => $friendRequest->receiver_id,
            ]);

            Friendship::create([
                'user_id' => $friendRequest->receiver_id,
                'friend_id' => $friendRequest->sender_id,
            ]);
        });
    }

    /**
     * Reject a friend request.
     *
     * @param FriendRequest $friendRequest
     * @return void
     */
    public function rejectFriendRequest(FriendRequest $friendRequest): void
    {
        $friendRequest->update(['status' => FriendRequestStatus::REJECTED]);
    }

    /**
     * Get user's friends.
     *
     * @param User $user
     * @return Collection
     */
    public function getFriends(User $user): Collection
    {
        return $user->friends()->get();
    }

    /**
     * Check if two users are friends.
     *
     * @param int $userId1
     * @param int $userId2
     * @return bool
     */
    public function areFriends(int $userId1, int $userId2): bool
    {
        return Friendship::where('user_id', $userId1)
            ->where('friend_id', $userId2)
            ->exists();
    }
}

