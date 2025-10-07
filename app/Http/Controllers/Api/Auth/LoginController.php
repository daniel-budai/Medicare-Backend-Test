<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {
    }

    /**
     * Login a user.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->validated())) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        try {
            // Service handles email verification check
            $token = $this->authService->createToken($user);

            return response()->json([
                'message' => 'Login successful.',
                'user' => new UserResource($user),
                'token' => $token,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Auth::logout();
            
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
                'email' => $user->email, // Frontend can use this to auto-populate resend form
            ], $e->status);
        }
    }
}

