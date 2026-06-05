<?php

namespace App\Repositories\Interfaces;

use App\Models\RefreshToken;

interface RefreshTokenRepositoryInterface
{
    public function create(array $data): RefreshToken;

    public function findValid(string $tokenHash): ?RefreshToken;

    public function revoke(RefreshToken $token): void;
}
