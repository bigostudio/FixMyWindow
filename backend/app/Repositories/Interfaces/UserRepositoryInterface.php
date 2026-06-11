<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Support\Collection;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function findById(int $id): ?User;

    public function emailExists(string $email): bool;

    public function create(array $data): User;

    public function getByRoles(array $roles): Collection;

    public function delete(User $user): void;
}
