<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\ConflictException;
use App\Models\Customer;
use App\Repositories\Interfaces\CustomerRepositoryInterface;
use App\Repositories\Interfaces\RefreshTokenRepositoryInterface;
use App\Support\Concerns\IssuesTokens;
use App\Support\Enums\CustomerType;
use Illuminate\Support\Facades\Hash;

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

    public function registerB2B(array $data): array
    {
        if ($this->customerRepository->findByEmail($data['email'])) {
            throw new ConflictException('An account with this email already exists.');
        }

        $customer = $this->customerRepository->createB2B([
            'name'     => $data['name'],
            'phone'    => $data['phone'],
            'email'    => $data['email'],
            'password' => $data['password'],
            'type'     => CustomerType::B2B,
        ]);

        return array_merge($this->issueTokens($customer, 'api'), ['customer' => $customer]);
    }

    public function loginB2B(string $email, string $password): array
    {
        $customer = $this->customerRepository->findByEmail($email);

        if (! $customer || ! Hash::check($password, $customer->password)) {
            throw new BusinessRuleException('Invalid credentials.');
        }

        return array_merge($this->issueTokens($customer, 'api'), ['customer' => $customer]);
    }
}
