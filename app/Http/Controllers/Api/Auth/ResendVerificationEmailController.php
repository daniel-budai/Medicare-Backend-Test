<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ResendVerificationEmailController extends Controller
{
    /**
     * Resend the email verification notification.
     *
     * @param ResendVerificationRequest $request
     * @return JsonResponse
     */
    public function __invoke(ResendVerificationRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->firstOrFail();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
            ], 200);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification email sent.',
        ], 200);
    }
}

