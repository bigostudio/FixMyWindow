<?php

namespace App\Repositories\Interfaces;

use App\Models\EmailVerificationToken;

interface EmailVerificationTokenRepositoryInterface
{
    public function create(array $data): EmailVerificationToken;

    public function findByToken(string $token): ?EmailVerificationToken;

    public function latestForModel(string $modelType, int $modelId): ?EmailVerificationToken;

    public function deleteForModel(string $modelType, int $modelId): void;
}
