<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\RateLimitException;
use App\Models\OtpRequest;
use App\Repositories\Interfaces\OtpRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    public function __construct(
        private readonly OtpRepositoryInterface $otpRepository,
    ) {}

    public function send(string $phone): void
    {
        // Check active lockout
        $locked = $this->otpRepository->findLockedRecord($phone);
        if ($locked) {
            throw new RateLimitException('Too many failed attempts. Please try again later.');
        }

        // Check resend rate limit: max 3 per hour
        $count = $this->otpRepository->countLastHour($phone);
        if ($count >= 3) {
            throw new RateLimitException('OTP resend limit exceeded. Please try again after an hour.');
        }

        $otp = app()->isProduction()
            ? str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT)
            : '123456';

        $this->otpRepository->create([
            'phone'      => $phone,
            'otp_hash'   => Hash::make($otp),
            'expires_at' => now()->addMinutes(5),
            'created_at' => now(),
        ]);

        // TODO: Cycle 2 — dispatch SendSmsJob with rendered otp_sent template
    }

    public function verify(string $phone, string $otp): OtpRequest
    {
        $record = $this->otpRepository->findLatestUnconsumed($phone);

        if (! $record) {
            throw new BusinessRuleException('No active OTP found for this phone number.');
        }

        if ($record->consumed_at !== null) {
            throw new BusinessRuleException('OTP has already been used.');
        }

        if ($record->expires_at->isPast()) {
            throw new BusinessRuleException('OTP has expired.');
        }

        if (! Hash::check($otp, $record->otp_hash)) {
            $record->failed_attempts += 1;

            if ($record->failed_attempts >= 5) {
                $record->locked_until = now()->addMinutes(15);
                $record->save();
                throw new RateLimitException('Too many failed attempts. Phone locked for 15 minutes.');
            }

            $record->save();
            throw new BusinessRuleException('Invalid OTP.');
        }

        $record->consumed_at = now();
        $record->save();

        return $record;
    }
}
