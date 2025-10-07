<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ListUsersRequest;
use App\Http\Resources\UserResource;
use App\Services\User\UserService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {
    }

    /**
     * Get a paginated list of active users.
     *
     * @param ListUsersRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(ListUsersRequest $request): AnonymousResourceCollection
    {
        $filters = $request->only(['name', 'email']);
        $perPage = $request->input('per_page', 15);

        $users = $this->userService->getActiveUsers($filters, $perPage);

        return UserResource::collection($users);
    }
}

