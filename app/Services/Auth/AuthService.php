<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;
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
     * Attempt to authenticate a user.
     *
     * @param array $credentials
     * @return User|null
     */
    public function attemptLogin(array $credentials): ?User
    {
        if (!Auth::attempt($credentials)) {
            return null;
        }

        return Auth::user();
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
     * Verify a user's email with the given hash.
     *
     * @param string $userId
     * @param string $hash
     * @return array{success: bool, message: string, status: int}
     */
    public function verifyEmail(string $userId, string $hash): array
    {
        $user = User::findOrFail($userId);

        // Verify the hash matches the user's email
        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return [
                'success' => false,
                'message' => 'Invalid verification link.',
                'status' => 403,
            ];
        }

        // Check if already verified
        if ($user->hasVerifiedEmail()) {
            return [
                'success' => true,
                'message' => 'Email already verified.',
                'status' => 200,
            ];
        }

        // Mark as verified
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return [
            'success' => true,
            'message' => 'Email verified successfully.',
            'status' => 200,
        ];
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

