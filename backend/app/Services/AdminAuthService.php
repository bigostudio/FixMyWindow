<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Repositories\Interfaces\RefreshTokenRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Support\Concerns\IssuesTokens;
use Illuminate\Support\Facades\Hash;

class AdminAuthService
{
    use IssuesTokens;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
    ) {}

    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new BusinessRuleException('Invalid credentials.');
        }

        if (! $user->is_active) {
            throw new BusinessRuleException('Account is inactive.');
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
