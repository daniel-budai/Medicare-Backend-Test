<?php

use App\Enums\FriendRequestStatus;
use App\Models\FriendRequest;
use App\Models\Friendship;
use App\Models\User;

describe('Friend Request Management', function () {
    
    test('authenticated user can send friend request to another user', function () {
        $sender = User::factory()->create(['email_verified_at' => now()]);
        $receiver = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($sender, 'sanctum')
            ->postJson('/api/friends/request', [
                'receiver_id' => $receiver->id,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Friend request sent successfully.',
            ])
            ->assertJsonStructure([
                'message',
                'friend_request' => [
                    'id',
                    'sender',
                    'receiver',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('friend_requests', [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => FriendRequestStatus::PENDING->value,
        ]);
    });

    test('user cannot send friend request to themselves', function () {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/friends/request', [
                'receiver_id' => $user->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['receiver_id']);
    });

    test('user cannot send duplicate friend request', function () {
        $sender = User::factory()->create(['email_verified_at' => now()]);
        $receiver = User::factory()->create(['email_verified_at' => now()]);

        FriendRequest::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => FriendRequestStatus::PENDING,
        ]);

        $response = $this->actingAs($sender, 'sanctum')
            ->postJson('/api/friends/request', [
                'receiver_id' => $receiver->id,
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'A pending friend request already exists with this user.',
            ]);
    });

    test('user cannot send friend request to non-existent user', function () {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/friends/request', [
                'receiver_id' => 99999, // Non-existent ID
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['receiver_id']);
    });

    test('user cannot send friend request to already existing friend', function () {
        $user1 = User::factory()->create(['email_verified_at' => now()]);
        $user2 = User::factory()->create(['email_verified_at' => now()]);

        Friendship::create(['user_id' => $user1->id, 'friend_id' => $user2->id]);
        Friendship::create(['user_id' => $user2->id, 'friend_id' => $user1->id]);

        $response = $this->actingAs($user1, 'sanctum')
            ->postJson('/api/friends/request', [
                'receiver_id' => $user2->id,
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Users are already friends.',
            ]);
    });

    test('receiver can accept friend request', function () {
        $sender = User::factory()->create(['email_verified_at' => now()]);
        $receiver = User::factory()->create(['email_verified_at' => now()]);

        $friendRequest = FriendRequest::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => FriendRequestStatus::PENDING,
        ]);

        $response = $this->actingAs($receiver, 'sanctum')
            ->postJson("/api/friends/requests/{$friendRequest->id}/accept");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Friend request accepted.',
            ]);

        $this->assertDatabaseHas('friend_requests', [
            'id' => $friendRequest->id,
            'status' => FriendRequestStatus::ACCEPTED->value,
        ]);

        $this->assertDatabaseHas('friendships', [
            'user_id' => $sender->id,
            'friend_id' => $receiver->id,
        ]);

        $this->assertDatabaseHas('friendships', [
            'user_id' => $receiver->id,
            'friend_id' => $sender->id,
        ]);
    });

    test('sender cannot accept their own friend request', function () {
        $sender = User::factory()->create(['email_verified_at' => now()]);
        $receiver = User::factory()->create(['email_verified_at' => now()]);

        $friendRequest = FriendRequest::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => FriendRequestStatus::PENDING,
        ]);

        $response = $this->actingAs($sender, 'sanctum')
            ->postJson("/api/friends/requests/{$friendRequest->id}/accept");

        $response->assertStatus(403); 
    });

    test('receiver can reject friend request', function () {
        $sender = User::factory()->create(['email_verified_at' => now()]);
        $receiver = User::factory()->create(['email_verified_at' => now()]);

        $friendRequest = FriendRequest::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => FriendRequestStatus::PENDING,
        ]);

        $response = $this->actingAs($receiver, 'sanctum')
            ->postJson("/api/friends/requests/{$friendRequest->id}/reject");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Friend request rejected.',
            ]);

        $this->assertDatabaseHas('friend_requests', [
            'id' => $friendRequest->id,
            'status' => FriendRequestStatus::REJECTED->value,
        ]);

        $this->assertDatabaseMissing('friendships', [
            'user_id' => $sender->id,
            'friend_id' => $receiver->id,
        ]);
    });

    test('user can view their pending friend requests', function () {
        $receiver = User::factory()->create(['email_verified_at' => now()]);
        $sender1 = User::factory()->create(['email_verified_at' => now()]);
        $sender2 = User::factory()->create(['email_verified_at' => now()]);

        FriendRequest::create([
            'sender_id' => $sender1->id,
            'receiver_id' => $receiver->id,
            'status' => FriendRequestStatus::PENDING,
        ]);

        FriendRequest::create([
            'sender_id' => $sender2->id,
            'receiver_id' => $receiver->id,
            'status' => FriendRequestStatus::PENDING,
        ]);

        FriendRequest::create([
            'sender_id' => User::factory()->create(['email_verified_at' => now()])->id,
            'receiver_id' => $receiver->id,
            'status' => FriendRequestStatus::ACCEPTED,
        ]);

        $response = $this->actingAs($receiver, 'sanctum')
            ->getJson('/api/friends/requests');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data') // Only 2 pending requests
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'sender',
                        'receiver',
                        'status',
                    ],
                ],
            ]);
    });

    test('user can view their friends list', function () {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $friend1 = User::factory()->create(['email_verified_at' => now()]);
        $friend2 = User::factory()->create(['email_verified_at' => now()]);

        Friendship::create(['user_id' => $user->id, 'friend_id' => $friend1->id]);
        Friendship::create(['user_id' => $user->id, 'friend_id' => $friend2->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/friends');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                    ],
                ],
            ]);
    });
});

