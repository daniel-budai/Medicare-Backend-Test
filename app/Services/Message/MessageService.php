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
     */
    public function sendMessage(User $sender, int $receiverId, string $messageText): Message
    {
        // Check if users are friends
        abort_unless(
            $this->friendService->areFriends($sender->id, $receiverId),
            403,
            'You can only send messages to your friends.'
        );

        $message = Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiverId,
            'message' => $messageText,
        ]);

        // Return with relationships loaded
        return $message->load(['sender', 'receiver']);
    }

    /**
     * Get messages between two users.
     *
     * @param User $user
     * @param int $friendId
     * @param int|null $perPage
     * @return LengthAwarePaginator
     */
    public function getMessagesBetweenUsers(User $user, int $friendId, ?int $perPage = null): LengthAwarePaginator
    {
        // Check if users are friends
        abort_unless(
            $this->friendService->areFriends($user->id, $friendId),
            403,
            'You can only view messages with your friends.'
        );

        // Use default pagination if not specified
        $perPage = $perPage ?? 20;

        return Message::with(['sender', 'receiver'])
            ->where(function ($query) use ($user, $friendId) {
                $query->where(function ($q) use ($user, $friendId) {
                    $q->where('sender_id', $user->id)
                        ->where('receiver_id', $friendId);
                })->orWhere(function ($q) use ($user, $friendId) {
                    $q->where('sender_id', $friendId)
                        ->where('receiver_id', $user->id);
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}

