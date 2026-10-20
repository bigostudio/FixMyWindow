<?php

namespace App\Services;

use App\Repositories\Interfaces\CustomerRepositoryInterface;
use App\Repositories\Interfaces\RefreshTokenRepositoryInterface;
use App\Support\Concerns\IssuesTokens;

class Msg91AuthService
{
    use IssuesTokens;

    public function __construct(
        private readonly Msg91OtpService $msg91OtpService,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
    ) {}

    public function sendOtp(string $phone): void
    {
        $this->msg91OtpService->send($phone);
    }

    public function retryOtp(string $phone, ?string $retryChannel): void
    {
        $this->msg91OtpService->retry($phone, $retryChannel);
    }

    public function verifyOtp(string $phone, string $otp): array
    {
        $this->msg91OtpService->verify($phone, $otp);

        $customer = $this->customerRepository->upsertByPhone($phone);

        return array_merge($this->issueTokens($customer, 'api'), ['customer' => $customer]);
    }
}
