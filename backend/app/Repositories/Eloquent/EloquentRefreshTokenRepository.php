<?php

namespace App\Repositories\Eloquent;

use App\Models\RefreshToken;
use App\Repositories\Interfaces\RefreshTokenRepositoryInterface;

class EloquentRefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function create(array $data): RefreshToken
    {
        return RefreshToken::create($data);
    }

    public function findValid(string $tokenHash): ?RefreshToken
    {
        return RefreshToken::where('token_hash', $tokenHash)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function revoke(RefreshToken $token): void
    {
        $token->update(['revoked_at' => now()]);
    }
}
