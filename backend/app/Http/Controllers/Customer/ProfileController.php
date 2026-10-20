<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\CustomerResource;
use App\Services\CustomerProfileService;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function __construct(
        private readonly CustomerProfileService $profileService,
    ) {}

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $customer = $this->profileService->updateProfile(
            auth('api')->user(),
            $request->validated(),
        );

        return response()->json(new CustomerResource($customer));
    }
}
