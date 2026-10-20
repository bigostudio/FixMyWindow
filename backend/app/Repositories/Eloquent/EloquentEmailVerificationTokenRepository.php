<?php

namespace App\Repositories\Eloquent;

use App\Models\EmailVerificationToken;
use App\Repositories\Interfaces\EmailVerificationTokenRepositoryInterface;

class EloquentEmailVerificationTokenRepository implements EmailVerificationTokenRepositoryInterface
{
    public function create(array $data): EmailVerificationToken
    {
        return EmailVerificationToken::create($data);
    }

    public function findByToken(string $token): ?EmailVerificationToken
    {
        return EmailVerificationToken::where('token', $token)->first();
    }

    public function latestForModel(string $modelType, int $modelId): ?EmailVerificationToken
    {
        return EmailVerificationToken::where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->latest('created_at')
            ->first();
    }

    public function deleteForModel(string $modelType, int $modelId): void
    {
        EmailVerificationToken::where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->delete();
    }
}
