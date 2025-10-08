<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\ResendVerificationEmailController;
use App\Http\Controllers\Api\Auth\VerifyEmailController;
use App\Http\Controllers\Api\FriendController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes - Authentication
Route::prefix('auth')->group(function () {
    Route::post('register', RegisterController::class);
    Route::post('login', LoginController::class);
});

// Email verification (public but signed)
Route::get('email/verify/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

// Resend verification email (public with heavy throttling)
Route::post('email/resend-verification', ResendVerificationEmailController::class)
    ->middleware('throttle:3,1')
    ->name('verification.send');

// Protected routes - Require authentication
Route::middleware(['auth:sanctum'])->group(function () {
    // Logout
    Route::post('auth/logout', LogoutController::class);

    // Protected routes - Require email verification
    Route::middleware(['verified'])->group(function () {
        // Users
        Route::get('users', [UserController::class, 'index']);

        // Friends
        Route::prefix('friends')->group(function () {
            Route::post('request', [FriendController::class, 'sendRequest']);
            Route::get('requests', [FriendController::class, 'pendingRequests']);
            Route::post('requests/{friendRequest}/accept', [FriendController::class, 'acceptRequest']);
            Route::post('requests/{friendRequest}/reject', [FriendController::class, 'rejectRequest']);
            Route::get('/', [FriendController::class, 'friends']);
        });

        // Messages
        Route::prefix('messages')->group(function () {
            Route::post('/', [MessageController::class, 'send']);
            Route::get('{friendId}', [MessageController::class, 'index']);
        });
    });
});

