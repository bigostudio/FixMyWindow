<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleGuard
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth()->user();

        if (! $user) {
            return new JsonResponse([
                'success' => false,
                'data'    => null,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $userRole = $user->role instanceof \BackedEnum
            ? $user->role->value
            : (string) $user->role;

        if (! in_array($userRole, $roles, true)) {
            return new JsonResponse([
                'success' => false,
                'data'    => null,
                'message' => 'Forbidden',
            ], 403);
        }

        return $next($request);
    }
}
