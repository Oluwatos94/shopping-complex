<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\User\Models\User;

class UserRepository
{
    /**
     * Find a user by ID
     *
     * @param int $id
     * @param array<string> $relations Relations to eager load to prevent N+1
     * @return User
     * @throws ModelNotFoundException
     */
    public function find(int $id, array $relations = []): User
    {
        $query = User::query();

        if (!empty($relations)) {
            $query->with($relations);
        }

        $user = $query->find($id);

        if (!$user) {
            throw new ModelNotFoundException("User with ID {$id} not found.");
        }

        return $user;
    }

    /**
     * Find a user by email address
     *
     * @param string $email
     * @param array<string> $relations
     * @return User|null
     */
    public function findByEmail(string $email, array $relations = []): ?User
    {
        $query = User::query()->where('email', $email);

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->first();
    }

    /**
     * Find users by role
     *
     * @param string $role Role: 'customer', 'vendor', or 'admin'
     * @param array<string> $relations
     * @return Collection<int, User>
     */
    public function findByRole(string $role, array $relations = []): Collection
    {
        $validRoles = ['customer', 'vendor', 'admin'];

        if (!in_array($role, $validRoles)) {
            throw new \InvalidArgumentException("Invalid role: {$role}. Must be one of: " . implode(', ', $validRoles));
        }

        $query = User::query()->where('role', $role);

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->get();
    }

    /**
     * Get all users with pagination
     *
     * @param int $perPage
     * @param array<string> $relations
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        $query = User::query();

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Create a new user
     *
     * @param array<string, mixed> $data
     * @return User
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create($data);

            return $user;
        });
    }

    /**
     * Update a user
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return User
     * @throws ModelNotFoundException
     */
    public function update(int $id, array $data): User
    {
        return DB::transaction(function () use ($id, $data) {
            $user = $this->find($id);
            $user->update($data);

            return $user->fresh();
        });
    }

    /**
     * Update user's role
     *
     * @param int $id
     * @param string $role
     * @return User
     * @throws ModelNotFoundException
     */
    public function updateRole(int $id, string $role): User
    {
        $validRoles = ['customer', 'vendor', 'admin'];

        if (!in_array($role, $validRoles)) {
            throw new \InvalidArgumentException("Invalid role: {$role}. Must be one of: " . implode(', ', $validRoles));
        }

        return DB::transaction(function () use ($id, $role) {
            $user = $this->find($id);
            $user->update(['role' => $role]);

            return $user->fresh();
        });
    }

    /**
     * Verify user's email
     *
     * @param int $id
     * @return User
     * @throws ModelNotFoundException
     */
    public function verifyEmail(int $id): User
    {
        return DB::transaction(function () use ($id) {
            $user = $this->find($id);

            if ($user->email_verified_at !== null) {
                return $user; // Already verified
            }

            $user->update(['email_verified_at' => now()]);

            return $user->fresh();
        });
    }

    /**
     * Verify user's email by email address
     *
     * @param string $email
     * @return User|null
     */
    public function verifyEmailByAddress(string $email): ?User
    {
        return DB::transaction(function () use ($email) {
            $user = $this->findByEmail($email);

            if (!$user) {
                return null;
            }

            if ($user->email_verified_at !== null) {
                return $user; // Already verified
            }

            $user->update(['email_verified_at' => now()]);

            return $user->fresh();
        });
    }

    /**
     * Delete a user
     *
     * @param int $id
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $user = $this->find($id);

            return $user->delete();
        });
    }

    /**
     * Get verified users
     *
     * @param array<string> $relations
     * @return Collection<int, User>
     */
    public function getVerifiedUsers(array $relations = []): Collection
    {
        $query = User::query()->whereNotNull('email_verified_at');

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->get();
    }

    /**
     * Get vendors (users with role 'vendor')
     *
     * @param array<string> $relations
     * @return Collection<int, User>
     */
    public function getVendors(array $relations = []): Collection
    {
        return $this->findByRole('vendor', $relations);
    }

    /**
     * Get customers (users with role 'customer')
     *
     * @param array<string> $relations
     * @return Collection<int, User>
     */
    public function getCustomers(array $relations = []): Collection
    {
        return $this->findByRole('customer', $relations);
    }

    /**
     * Get admins (users with role 'admin')
     *
     * @param array<string> $relations
     * @return Collection<int, User>
     */
    public function getAdmins(array $relations = []): Collection
    {
        return $this->findByRole('admin', $relations);
    }

    /**
     * Search users by name or email
     *
     * @param string $searchTerm
     * @param array<string> $relations
     * @return Collection<int, User>
     */
    public function search(string $searchTerm, array $relations = []): Collection
    {
        $query = User::query()
            ->where('name', 'like', "%{$searchTerm}%")
            ->orWhere('email', 'like', "%{$searchTerm}%");

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->get();
    }

    /**
     * Get users count by role
     *
     * @return array<string, int>
     */
    public function getUserCountsByRole(): array
    {
        $counts = User::query()
            ->select('role', DB::raw('count(*) as count'))
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();

        return [
            'customer' => $counts['customer'] ?? 0,
            'vendor' => $counts['vendor'] ?? 0,
            'admin' => $counts['admin'] ?? 0,
        ];
    }
}
