<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('User Registration', function () {
    
    test('user can register with valid data', function () {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Registration successful. Please check your email to verify your account.',
            ])
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
            ])
            ->assertJsonMissing(['password']); // Security: no password in response

        // Assert user exists in database
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Assert password is hashed (not plain text)
        $user = User::where('email', 'john@example.com')->first();
        expect(Hash::check('SecurePass123!', $user->password))->toBeTrue();

        expect($user->email_verified_at)->toBeNull();
    });

    test('registration requires all fields', function () {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    });

    test('registration validates email format', function () {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'not-an-email', //@
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('registration requires unique email', function () {
        // Create existing user
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('registration requires password confirmation', function () {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'DifferentPassword!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    test('registration enforces password strength requirements', function () {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    test('registration respects maximum field lengths', function () {
        $response = $this->postJson('/api/auth/register', [
            'name' => str_repeat('a', 256), // Exceeds 255 max
            'email' => 'john@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });
});

