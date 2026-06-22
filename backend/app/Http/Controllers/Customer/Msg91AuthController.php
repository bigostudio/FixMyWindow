<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Msg91RetryOtpRequest;
use App\Http\Requests\Msg91SendOtpRequest;
use App\Http\Requests\Msg91VerifyOtpRequest;
use App\Http\Resources\CustomerResource;
use App\Services\Msg91AuthService;
use Illuminate\Http\JsonResponse;

class Msg91AuthController extends Controller
{
    public function __construct(
        private readonly Msg91AuthService $msg91AuthService,
    ) {}

    public function sendOtp(Msg91SendOtpRequest $request): JsonResponse
    {
        $this->msg91AuthService->sendOtp($request->validated('phone'));

        return response()->json(['message' => 'OTP sent successfully.']);
    }

    public function retryOtp(Msg91RetryOtpRequest $request): JsonResponse
    {
        $this->msg91AuthService->retryOtp(
            $request->validated('phone'),
            $request->validated('retryChannel'),
        );

        return response()->json(['message' => 'OTP resent successfully.']);
    }

    public function verifyOtp(Msg91VerifyOtpRequest $request): JsonResponse
    {
        $result = $this->msg91AuthService->verifyOtp(
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
}
