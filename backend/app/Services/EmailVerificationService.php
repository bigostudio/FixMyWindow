<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Mail\EmailVerificationMail;
use App\Models\Customer;
use App\Models\User;
use App\Repositories\Interfaces\EmailVerificationTokenRepositoryInterface;
use Illuminate\Support\Facades\Mail;

class EmailVerificationService
{
    public function __construct(
        private readonly EmailVerificationTokenRepositoryInterface $tokenRepository,
    ) {}

    public function send(object $model, string $modelType): void
    {
        $this->tokenRepository->deleteForModel($modelType, $model->id);

        $plainToken = bin2hex(random_bytes(32)); // 64-char hex string

        $this->tokenRepository->create([
            'model_type' => $modelType,
            'model_id'   => $model->id,
            'email'      => $model->email,
            'token'      => $plainToken,
            'expires_at' => now()->addHours(24),
            'created_at' => now(),
        ]);

        Mail::to($model->email)->send(new EmailVerificationMail($plainToken, $model->name));
    }

    public function verify(string $token): void
    {
        $record = $this->tokenRepository->findByToken($token);

        if (! $record) {
            throw new BusinessRuleException('Invalid or expired verification link.');
        }

        if ($record->isExpired()) {
            $this->tokenRepository->deleteForModel($record->model_type, $record->model_id);
            throw new BusinessRuleException('Verification link has expired. Please request a new one.');
        }

        $modelClass = $record->model_type;
        $model = $modelClass::findOrFail($record->model_id);
        $model->update(['email_verified_at' => now()]);

        $this->tokenRepository->deleteForModel($record->model_type, $record->model_id);
    }

    public function resendForCustomer(string $email): void
    {
        $customer = Customer::where('email', $email)->first();

        if (! $customer || ! $customer->email) {
            return; // silently ignore — do not reveal if email is registered
        }

        if ($customer->email_verified_at) {
            throw new BusinessRuleException('Email address is already verified.');
        }

        $latest = $this->tokenRepository->latestForModel(Customer::class, $customer->id);
        if ($latest && $latest->created_at->gt(now()->subMinute())) {
            throw new BusinessRuleException('Please wait a moment before requesting another verification email.');
        }

        $this->send($customer, Customer::class);
    }

    public function resendForUser(User $user): void
    {
        if ($user->email_verified_at) {
            throw new BusinessRuleException('Email address is already verified.');
        }

        $latest = $this->tokenRepository->latestForModel(User::class, $user->id);
        if ($latest && $latest->created_at->gt(now()->subMinute())) {
            throw new BusinessRuleException('Please wait a moment before requesting another verification email.');
        }

        $this->send($user, User::class);
    }
}
