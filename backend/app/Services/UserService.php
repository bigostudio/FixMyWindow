<?php

namespace App\Services;

use App\Exceptions\ConflictException;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function create(array $data): User
    {
        if ($this->userRepository->emailExists($data['email'])) {
            throw new ConflictException('A user with this email address already exists.');
        }

        return $this->userRepository->create($data);
    }
}
