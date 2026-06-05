<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminRefreshRequest;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Resources\CustomerResource;
use App\Services\CustomerAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly CustomerAuthService $authService) {}

    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $this->authService->sendOtp($request->validated('phone'));

        return response()->json(['message' => 'OTP sent successfully.']);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $result = $this->authService->verifyOtp(
            $request->validated('phone'),
            $request->validated('otp'),
        );

        return response()->json([
            'customer'      => new CustomerResource($result['customer']),
            'access_token'  => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
            'token_type'    => $result['token_type'],
            'expires_in'    => $result['expires_in'],
        ]);
    }

    public function refresh(AdminRefreshRequest $request): JsonResponse
    {
        $tokens = $this->authService->refresh($request->validated('refresh_token'));

        return response()->json($tokens);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->input('refresh_token', ''));

        return response()->json(['message' => 'Logged out successfully.']);
    }
}
