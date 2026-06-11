<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return response()->json(new UserResource($user), 201);
    }

    public function approve(int $id): JsonResponse
    {
        $user = $this->userService->approve($id);

        return response()->json([
            'user'    => new UserResource($user),
            'message' => 'User approved successfully.',
        ]);
    }

    public function reject(int $id): JsonResponse
    {
        $this->userService->reject($id);

        return response()->json(['message' => 'User rejected and removed.']);
    }
}
