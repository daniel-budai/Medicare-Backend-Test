<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Friend\SendFriendRequestRequest;
use App\Http\Resources\FriendRequestResource;
use App\Http\Resources\UserResource;
use App\Models\FriendRequest;
use App\Services\Friend\FriendService;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FriendController extends Controller
{
    public function __construct(
        protected FriendService $friendService,
        protected UserService $userService
    ) {
    }

    /**
     * Send a friend request.
     *
     * @param SendFriendRequestRequest $request
     * @return JsonResponse
     */
    public function sendRequest(SendFriendRequestRequest $request): JsonResponse
    {
        try {
            $receiver = $this->userService->findActiveUser($request->receiver_id);

            if (!$receiver) {
                return response()->json([
                    'message' => 'The user you are trying to add is not active.',
                ], 400);
            }

            $friendRequest = $this->friendService->sendFriendRequest(
                $request->user(),
                $request->receiver_id
            );

            return response()->json([
                'message' => 'Friend request sent successfully.',
                'friend_request' => new FriendRequestResource($friendRequest->load(['sender', 'receiver'])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get pending friend requests.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function pendingRequests(Request $request): AnonymousResourceCollection
    {
        $pendingRequests = $this->friendService->getPendingRequests($request->user());

        return FriendRequestResource::collection($pendingRequests);
    }

    /**
     * Accept a friend request.
     *
     * @param Request $request
     * @param FriendRequest $friendRequest
     * @return JsonResponse
     */
    public function acceptRequest(Request $request, FriendRequest $friendRequest): JsonResponse
    {
        $this->authorize('accept', $friendRequest);

        try {
            $this->friendService->acceptFriendRequest($friendRequest);

            return response()->json([
                'message' => 'Friend request accepted.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reject a friend request.
     *
     * @param Request $request
     * @param FriendRequest $friendRequest
     * @return JsonResponse
     */
    public function rejectRequest(Request $request, FriendRequest $friendRequest): JsonResponse
    {
        $this->authorize('reject', $friendRequest);

        try {
            $this->friendService->rejectFriendRequest($friendRequest);

            return response()->json([
                'message' => 'Friend request rejected.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get user's friends.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function friends(Request $request): AnonymousResourceCollection
    {
        $friends = $this->friendService->getFriends($request->user());

        return UserResource::collection($friends);
    }
}

