<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register a new user.
     *
     * @param array $data
     * @return User
     */
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        event(new Registered($user));

        return $user;
    }

    /**
     * Create an authentication token for the user.
     * 
     * Validates that the user's email is verified before creating a token.
     *
     * @param User $user
     * @param string $deviceName
     * @return string
     * @throws ValidationException
     */
    public function createToken(User $user, string $deviceName = 'api-token'): string
    {
        // Ensure email is verified before issuing token
        if (!$user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => 'Your email address is not verified. Please check your email for a verification link.',
            ])->status(403);
        }

        return $user->createToken($deviceName)->plainTextToken;
    }

    /**
     * Revoke all tokens for the user.
     *
     * @param User $user
     * @return void
     */
    public function revokeTokens(User $user): void
    {
        $user->tokens()->delete();
    }
}

