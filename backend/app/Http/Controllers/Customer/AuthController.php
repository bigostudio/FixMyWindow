<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminRefreshRequest;
use App\Http\Requests\B2BLoginRequest;
use App\Http\Requests\B2BRegisterRequest;
use App\Http\Requests\RegisterCustomerRequest;
use App\Http\Requests\ResendVerificationRequest;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Resources\CustomerResource;
use App\Services\CustomerAuthService;
use App\Services\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly CustomerAuthService $authService,
        private readonly EmailVerificationService $emailVerificationService,
    ) {}

    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $this->authService->sendOtp($request->validated('phone'));

        return response()->json(['message' => 'OTP sent successfully.']);
    }

    public function register(RegisterCustomerRequest $request): JsonResponse
    {
        $result = $this->authService->register(
            $request->validated('phone'),
            $request->validated('name'),
        );

        return response()->json([
            'customer' => new CustomerResource($result['customer']),
            'message'  => 'Account created. Please verify your phone number to login.',
        ], 201);
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

    public function registerB2B(B2BRegisterRequest $request): JsonResponse
    {
        $result = $this->authService->registerB2B($request->validated());

        return response()->json([
            'customer' => new CustomerResource($result['customer']),
            'message'  => 'Account created. Please verify your email address to log in.',
        ], 201);
    }

    public function loginB2B(B2BLoginRequest $request): JsonResponse
    {
        $result = $this->authService->loginB2B(
            $request->validated('email'),
            $request->validated('password'),
        );

        return response()->json([
            'customer'      => new CustomerResource($result['customer']),
            'access_token'  => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
            'token_type'    => $result['token_type'],
            'expires_in'    => $result['expires_in'],
        ]);
    }

    public function verifyEmail(string $token): JsonResponse
    {
        $this->emailVerificationService->verify($token);

        return response()->json(['message' => 'Email verified successfully. You can now log in.']);
    }

    public function resendVerification(ResendVerificationRequest $request): JsonResponse
    {
        $this->emailVerificationService->resendForCustomer($request->validated('email'));

        return response()->json(['message' => 'If that email is registered, a verification link has been sent.']);
    }
}
