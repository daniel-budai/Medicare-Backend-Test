<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ChatApiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates sample users for testing the Chat API.
     * All users are created with verified emails and password 'password'.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Alice Johnson',
                'email' => 'alice@example.com',
            ],
            [
                'name' => 'Bob Smith',
                'email' => 'bob@example.com',
            ],
            [
                'name' => 'Charlie Brown',
                'email' => 'charlie@example.com',
            ],
            [
                'name' => 'Diana Prince',
                'email' => 'diana@example.com',
            ],
            [
                'name' => 'Edward Norton',
                'email' => 'edward@example.com',
            ],
        ];

        foreach ($users as $userData) {
            User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }

        $this->command->info('Created 5 test users with verified emails.');
        $this->command->info('All users have password: password');
        $this->command->newLine();
        $this->command->info('You can now:');
        $this->command->info('1. Login as any user (e.g., alice@example.com / password)');
        $this->command->info('2. Send friend requests between users');
        $this->command->info('3. Test messaging functionality');
    }
}

