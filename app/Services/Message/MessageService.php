<?php

namespace App\Services\Message;

use App\Models\Message;
use App\Models\User;
use App\Services\Friend\FriendService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MessageService
{
    public function __construct(
        protected FriendService $friendService
    ) {
    }

    /**
     * Send a message between friends.
     *
     * @param User $sender
     * @param int $receiverId
     * @param string $messageText
     * @return Message
     * @throws \Exception
     */
    public function sendMessage(User $sender, int $receiverId, string $messageText): Message
    {
        // Check if users are friends
        if (!$this->friendService->areFriends($sender->id, $receiverId)) {
            throw new \Exception('You can only send messages to your friends.');
        }

        return Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiverId,
            'message' => $messageText,
        ]);
    }

    /**
     * Get messages between two users.
     *
     * @param User $user
     * @param int $friendId
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws \Exception
     */
    public function getMessagesBetweenUsers(User $user, int $friendId, int $perPage = 20): LengthAwarePaginator
    {
        // Check if users are friends
        if (!$this->friendService->areFriends($user->id, $friendId)) {
            throw new \Exception('You can only view messages with your friends.');
        }

        return Message::with(['sender', 'receiver'])
            ->where(function ($query) use ($user, $friendId) {
                $query->where('sender_id', $user->id)
                    ->where('receiver_id', $friendId);
            })
            ->orWhere(function ($query) use ($user, $friendId) {
                $query->where('sender_id', $friendId)
                    ->where('receiver_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}

