<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Repositories\Interfaces\OtpRepositoryInterface;
use Illuminate\Support\Facades\Http;

class Msg91OtpService
{
    private string $authKey;
    private string $widgetId;


    public function __construct(
        private readonly OtpRepositoryInterface $otpRepository,
    ) {
        $this->authKey  = config('services.msg91.auth_key');
        $this->widgetId = config('services.msg91.widget_id');
    }

    public function send(string $phone): void
    {
        $response = Http::withHeaders([
            'authkey'      => $this->authKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.msg91.com/api/v5/widget/sendOtp', [
            'widgetId'   => $this->widgetId,
            'identifier' => $phone,
        ]);

        \Log::info('MSG91 send-otp response', [
            'status' => $response->status(),
            'body'   => $response->json(),
        ]);

        if (! $response->successful()) {
            throw new BusinessRuleException('Failed to send OTP. Please try again.');
        }

        $body = $response->json();

        if (! isset($body['type']) || $body['type'] !== 'success') {
            throw new BusinessRuleException($body['message'] ?? 'Failed to send OTP. Please try again.');
        }

        $this->otpRepository->create([
            'phone'      => $phone,
            'req_id'     => $body['message'],
            'created_at' => now(),
        ]);
    }

    public function retry(string $phone, ?string $retryChannel): void
    {
        $record = $this->otpRepository->findLatestUnconsumed($phone);

        if (! $record || ! $record->req_id) {
            throw new BusinessRuleException('No OTP request found. Please request a new OTP.');
        }

        $response = Http::withHeaders([
            'authkey'      => $this->authKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.msg91.com/api/v5/widget/retryOtp', [
            'widgetId'     => $this->widgetId,
            'reqId'        => $record->req_id,
            'retryChannel' => $retryChannel ?? '',
        ]);

        \Log::info('MSG91 retry-otp response', [
            'status' => $response->status(),
            'body'   => $response->json(),
        ]);

        if (! $response->successful()) {
            throw new BusinessRuleException('Failed to retry OTP. Please try again.');
        }

        $body = $response->json();

        if (! isset($body['type']) || $body['type'] !== 'success') {
            throw new BusinessRuleException($body['message'] ?? 'Failed to retry OTP. Please try again.');
        }
    }

    public function verify(string $phone, string $otp): void
    {
        $record = $this->otpRepository->findLatestUnconsumed($phone);

        if (! $record || ! $record->req_id) {
            throw new BusinessRuleException('No OTP request found. Please request a new OTP.');
        }

        $response = Http::withHeaders([
            'authkey'      => $this->authKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.msg91.com/api/v5/widget/verifyOtp', [
            'widgetId' => $this->widgetId,
            'reqId'    => $record->req_id,
            'otp'      => $otp,
        ]);

        \Log::info('MSG91 verify-otp response', [
            'status' => $response->status(),
            'body'   => $response->json(),
        ]);

        if (! $response->successful()) {
            throw new BusinessRuleException('OTP verification failed. Please try again.');
        }

        $body = $response->json();

        if (! isset($body['type']) || $body['type'] !== 'success') {
            throw new BusinessRuleException($body['message'] ?? 'Invalid OTP.');
        }

        $record->update(['consumed_at' => now()]);
    }
}
