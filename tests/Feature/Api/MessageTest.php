<?php

use App\Models\Friendship;
use App\Models\Message;
use App\Models\User;

describe('Message Management', function () {
    
    test('user can send message to a friend', function () {
        $sender = User::factory()->create(['email_verified_at' => now()]);
        $receiver = User::factory()->create(['email_verified_at' => now()]);

        // Make them friends
        Friendship::create(['user_id' => $sender->id, 'friend_id' => $receiver->id]);
        Friendship::create(['user_id' => $receiver->id, 'friend_id' => $sender->id]);

        $response = $this->actingAs($sender, 'sanctum')
            ->postJson('/api/messages', [
                'receiver_id' => $receiver->id,
                'message' => 'Hello, friend!',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Message sent successfully.',
            ])
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'sender',
                    'receiver',
                    'message',
                ],
            ]);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'message' => 'Hello, friend!',
        ]);
    });

    test('user cannot send message to non-friend', function () {
        $sender = User::factory()->create(['email_verified_at' => now()]);
        $stranger = User::factory()->create(['email_verified_at' => now()]);

        // No friendship exists

        $response = $this->actingAs($sender, 'sanctum')
            ->postJson('/api/messages', [
                'receiver_id' => $stranger->id,
                'message' => 'Hello, stranger!',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'You can only send messages to your friends.',
            ]);

        $this->assertDatabaseMissing('messages', [
            'sender_id' => $sender->id,
            'receiver_id' => $stranger->id,
        ]);
    });

    test('sending message requires receiver_id', function () {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/messages', [
                'message' => 'Hello!',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['receiver_id']);
    });

    test('sending message requires message content', function () {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $friend = User::factory()->create(['email_verified_at' => now()]);

        Friendship::create(['user_id' => $user->id, 'friend_id' => $friend->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/messages', [
                'receiver_id' => $friend->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    });

    test('user cannot send message to themselves', function () {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/messages', [
                'receiver_id' => $user->id,
                'message' => 'Hello, me!',
            ]);

        // Returns 403 because user is not friends with themselves 
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'You can only send messages to your friends.',
            ]);
    });

    test('user can retrieve messages with a friend', function () {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $friend = User::factory()->create(['email_verified_at' => now()]);

        Friendship::create(['user_id' => $user->id, 'friend_id' => $friend->id]);
        Friendship::create(['user_id' => $friend->id, 'friend_id' => $user->id]);

        Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $friend->id,
            'message' => 'Hello!',
        ]);

        Message::create([
            'sender_id' => $friend->id,
            'receiver_id' => $user->id,
            'message' => 'Hi there!',
        ]);

        Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $friend->id,
            'message' => 'How are you?',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/messages/{$friend->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'sender',
                        'receiver',
                        'message',
                    ],
                ],
                'links',
                'meta',
            ]);

        expect($response->json('data'))->toHaveCount(3);
    });

    test('user cannot retrieve messages with non-friend', function () {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $stranger = User::factory()->create(['email_verified_at' => now()]);


        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/messages/{$stranger->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'You can only view messages with your friends.',
            ]);
    });

    test('messages are ordered by most recent first', function () {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $friend = User::factory()->create(['email_verified_at' => now()]);

        Friendship::create(['user_id' => $user->id, 'friend_id' => $friend->id]);
        Friendship::create(['user_id' => $friend->id, 'friend_id' => $user->id]);

        Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $friend->id,
            'message' => 'First message',
        ]);
        sleep(1);

        Message::create([
            'sender_id' => $friend->id,
            'receiver_id' => $user->id,
            'message' => 'Second message',
        ]);
        sleep(1);

        Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $friend->id,
            'message' => 'Third message',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/messages/{$friend->id}");

        $messages = $response->json('data');

        // Assert messages are ordered newest first
        expect($messages[0]['message'])->toBe('Third message')
            ->and($messages[1]['message'])->toBe('Second message')
            ->and($messages[2]['message'])->toBe('First message');
    });
});

