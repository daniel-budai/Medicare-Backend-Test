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
     * @throws \Exception
     */
    public function sendFriendRequest(User $sender, int $receiverId): FriendRequest
    {
        // Check if users are already friends
        if ($this->areFriends($sender->id, $receiverId)) {
            throw new \Exception('Users are already friends.');
        }

        // Check if there's already a pending request
        $existingRequest = FriendRequest::where(function ($query) use ($sender, $receiverId) {
            $query->where('sender_id', $sender->id)
                ->where('receiver_id', $receiverId);
        })->orWhere(function ($query) use ($sender, $receiverId) {
            $query->where('sender_id', $receiverId)
                ->where('receiver_id', $sender->id);
        })->where('status', FriendRequestStatus::PENDING)
            ->first();

        if ($existingRequest) {
            throw new \Exception('A friend request already exists between these users.');
        }

        return FriendRequest::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiverId,
            'status' => FriendRequestStatus::PENDING,
        ]);
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
     * @throws \Exception
     */
    public function acceptFriendRequest(FriendRequest $friendRequest): void
    {
        $this->ensurePending($friendRequest);

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
     * @throws \Exception
     */
    public function rejectFriendRequest(FriendRequest $friendRequest): void
    {
        $this->ensurePending($friendRequest);

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

    /**
     * Ensure the friend request is still pending.
     *
     * @param FriendRequest $friendRequest
     * @return void
     * @throws \Exception
     */
    private function ensurePending(FriendRequest $friendRequest): void
    {
        if ($friendRequest->status !== FriendRequestStatus::PENDING) {
            throw new \Exception('This friend request has already been processed.');
        }
    }
}

