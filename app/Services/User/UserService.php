<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
    /**
     * Get a paginated list of active users.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActiveUsers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::whereNotNull('email_verified_at');

        // Filter by name if provided
        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        // Filter by email if provided
        if (!empty($filters['email'])) {
            $query->where('email', 'like', '%' . $filters['email'] . '%');
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Find an active user by ID.
     *
     * @param int $userId
     * @return User|null
     */
    public function findActiveUser(int $userId): ?User
    {
        return User::whereNotNull('email_verified_at')->find($userId);
    }
}

