<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Friend\SendFriendRequestRequest;
use App\Http\Resources\FriendRequestResource;
use App\Http\Resources\UserResource;
use App\Models\FriendRequest;
use App\Services\Friend\FriendService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FriendController extends Controller
{
    public function __construct(
        protected FriendService $friendService
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
        $friendRequest = $this->friendService->sendFriendRequest(
            $request->user(),
            $request->receiver_id
        );

        return response()->json([
            'message' => 'Friend request sent successfully.',
            'friend_request' => new FriendRequestResource($friendRequest),
        ], 201);
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
     * @param FriendRequest $friendRequest
     * @return JsonResponse
     */
    public function acceptRequest(FriendRequest $friendRequest): JsonResponse
    {
        $this->authorize('accept', $friendRequest);

        $this->friendService->acceptFriendRequest($friendRequest);

        return response()->json([
            'message' => 'Friend request accepted.',
        ]);
    }

    /**
     * Reject a friend request.
     *
     * @param FriendRequest $friendRequest
     * @return JsonResponse
     */
    public function rejectRequest(FriendRequest $friendRequest): JsonResponse
    {
        $this->authorize('reject', $friendRequest);

        $this->friendService->rejectFriendRequest($friendRequest);

        return response()->json([
            'message' => 'Friend request rejected.',
        ]);
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

