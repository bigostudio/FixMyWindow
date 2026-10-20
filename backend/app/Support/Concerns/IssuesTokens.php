<?php

namespace App\Support\Concerns;

use App\Models\RefreshToken;
use App\Repositories\Interfaces\RefreshTokenRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

trait IssuesTokens
{
    protected function issueTokens(Model $user, string $guard): array
    {
        $accessToken = auth($guard)->login($user);

        $rawRefresh = Str::random(64);

        /** @var RefreshTokenRepositoryInterface $repo */
        $repo = app(RefreshTokenRepositoryInterface::class);

        $repo->create([
            'tokenable_type' => get_class($user),
            'tokenable_id'   => $user->getKey(),
            'token_hash'     => Hash::make($rawRefresh),
            'expires_at'     => now()->addMinutes((int) config('jwt.refresh_ttl', 43200)),
        ]);

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $rawRefresh,
            'token_type'    => 'bearer',
            'expires_in'    => (int) config('jwt.ttl', 1440) * 60,
        ];
    }

    protected function resolveRefreshToken(string $rawToken, string $modelClass): array
    {
        /** @var RefreshTokenRepositoryInterface $repo */
        $repo = app(RefreshTokenRepositoryInterface::class);

        $tokens = RefreshToken::where('tokenable_type', $modelClass)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->get();

        $matched = $tokens->first(fn ($t) => Hash::check($rawToken, $t->token_hash));

        if (! $matched) {
            throw new \App\Exceptions\BusinessRuleException('Invalid or expired refresh token.');
        }

        return [$matched, $matched->tokenable];
    }
}
