<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {
    }

    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param Request $request
     * @param string $id
     * @param string $hash
     * @return JsonResponse
     */
    public function __invoke(Request $request, string $id, string $hash): JsonResponse
    {
        $result = $this->authService->verifyEmail($id, $hash);

        return response()->json([
            'message' => $result['message'],
        ], $result['status']);
    }
}

