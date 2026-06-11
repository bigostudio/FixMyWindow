<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\ConflictException;
use App\Mail\AdminPendingApprovalMail;
use App\Mail\AdminRegistrationReceivedMail;
use App\Models\User;
use App\Repositories\Interfaces\RefreshTokenRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Support\Concerns\IssuesTokens;
use App\Support\Enums\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AdminAuthService
{
    use IssuesTokens;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
    ) {}

    public function register(array $data): User
    {
        if ($this->userRepository->emailExists($data['email'])) {
            throw new ConflictException('An account with this email already exists.');
        }

        $user = $this->userRepository->create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'],
            'password'  => $data['password'],
            'role'      => $data['role'],
            'is_active' => false,
        ]);

        $approvers = $this->userRepository->getByRoles([
            Role::SuperAdmin->value,
            Role::OpsAdmin->value,
        ]);

        foreach ($approvers as $approver) {
            Mail::to($approver->email)->send(new AdminPendingApprovalMail($user));
        }

        Mail::to($user->email)->send(new AdminRegistrationReceivedMail($user));

        return $user;
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new BusinessRuleException('Invalid credentials.');
        }

        if (! $user->is_active) {
            throw new BusinessRuleException('Your account is pending approval. Please wait for an admin to activate it.');
        }

        $tokens = $this->issueTokens($user, 'admin');

        return array_merge($tokens, ['user' => $user]);
    }

    public function refresh(string $rawToken): array
    {
        [$refreshToken, $user] = $this->resolveRefreshToken($rawToken, User::class);

        $this->refreshTokenRepository->revoke($refreshToken);

        return $this->issueTokens($user, 'admin');
    }

    public function logout(string $rawToken): void
    {
        [$refreshToken] = $this->resolveRefreshToken($rawToken, User::class);

        $this->refreshTokenRepository->revoke($refreshToken);

        auth('admin')->logout();
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw new BusinessRuleException('Current password is incorrect.');
        }

        $user->update(['password' => $newPassword]);
    }
}
