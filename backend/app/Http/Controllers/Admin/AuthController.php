<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminChangePasswordRequest;
use App\Http\Requests\AdminLoginRequest;
use App\Http\Requests\AdminRefreshRequest;
use App\Http\Resources\UserResource;
use App\Services\AdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AdminAuthService $authService) {}

    public function login(AdminLoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->validated('email'),
            $request->validated('password'),
        );

        return response()->json([
            'user'          => new UserResource($result['user']),
            'access_token'  => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
            'token_type'    => $result['token_type'],
            'expires_in'    => $result['expires_in'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->input('refresh_token', ''));

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function refresh(AdminRefreshRequest $request): JsonResponse
    {
        $tokens = $this->authService->refresh($request->validated('refresh_token'));

        return response()->json($tokens);
    }

    public function changePassword(AdminChangePasswordRequest $request): JsonResponse
    {
        $this->authService->changePassword(
            auth('admin')->user(),
            $request->validated('current_password'),
            $request->validated('new_password'),
        );

        return response()->json(['message' => 'Password changed successfully.']);
    }
}
