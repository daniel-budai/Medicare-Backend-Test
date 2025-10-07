<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Message\ListMessagesRequest;
use App\Http\Requests\Message\SendMessageRequest;
use App\Http\Resources\MessageResource;
use App\Services\Message\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MessageController extends Controller
{
    public function __construct(
        protected MessageService $messageService
    ) {
    }

    /**
     * Send a message.
     *
     * @param SendMessageRequest $request
     * @return JsonResponse
     */
    public function send(SendMessageRequest $request): JsonResponse
    {
        try {
            $message = $this->messageService->sendMessage(
                $request->user(),
                $request->receiver_id,
                $request->message
            );

            return response()->json([
                'message' => 'Message sent successfully.',
                'data' => new MessageResource($message->load(['sender', 'receiver'])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get messages with a friend.
     *
     * @param ListMessagesRequest $request
     * @param int $friendId
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index(ListMessagesRequest $request, int $friendId): AnonymousResourceCollection|JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 20);

            $messages = $this->messageService->getMessagesBetweenUsers(
                $request->user(),
                $friendId,
                $perPage
            );

            return MessageResource::collection($messages);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}

