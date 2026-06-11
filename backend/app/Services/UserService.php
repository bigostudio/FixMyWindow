<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\ConflictException;
use App\Mail\AdminApprovedMail;
use App\Mail\AdminRejectedMail;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Mail;

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

        return $this->userRepository->create(array_merge($data, [
            'is_active'         => true,
            'email_verified_at' => now(),
        ]));
    }

    public function approve(int $id): User
    {
        $user = $this->userRepository->findById($id);

        if (! $user) {
            throw new BusinessRuleException('User not found.');
        }

        if ($user->is_active) {
            throw new BusinessRuleException('User is already active.');
        }

        $user->update(['is_active' => true]);

        Mail::to($user->email)->send(new AdminApprovedMail($user->name));

        return $user->fresh();
    }

    public function reject(int $id): void
    {
        $user = $this->userRepository->findById($id);

        if (! $user) {
            throw new BusinessRuleException('User not found.');
        }

        if ($user->is_active) {
            throw new BusinessRuleException('Cannot reject an already active user.');
        }

        Mail::to($user->email)->send(new AdminRejectedMail($user->name));

        $this->userRepository->delete($user);
    }
}
