<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Customer;
use App\Repositories\Interfaces\CustomerRepositoryInterface;
use App\Repositories\Interfaces\RefreshTokenRepositoryInterface;
use App\Support\Concerns\IssuesTokens;

class CustomerAuthService
{
    use IssuesTokens;

    public function __construct(
        private readonly OtpService $otpService,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
    ) {}

    public function sendOtp(string $phone): void
    {
        $this->otpService->send($phone);
    }

    public function verifyOtp(string $phone, string $otp): array
    {
        $this->otpService->verify($phone, $otp);

        $customer = $this->customerRepository->upsertByPhone($phone);

        $tokens = $this->issueTokens($customer, 'api');

        return array_merge($tokens, ['customer' => $customer]);
    }

    public function refresh(string $rawToken): array
    {
        [$refreshToken, $customer] = $this->resolveRefreshToken($rawToken, Customer::class);

        $this->refreshTokenRepository->revoke($refreshToken);

        return $this->issueTokens($customer, 'api');
    }

    public function logout(string $rawToken): void
    {
        [$refreshToken] = $this->resolveRefreshToken($rawToken, Customer::class);

        $this->refreshTokenRepository->revoke($refreshToken);

        auth('api')->logout();
    }
}
