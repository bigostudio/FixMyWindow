<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Resources\StaffResource;
use App\Http\Resources\StaffWithStatsResource;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function staff(): JsonResponse
    {
        $staff = $this->userService->getActiveStaff();

        return response()->json([
            'items' => StaffResource::collection($staff),
        ]);
    }

    public function pending(Request $request): JsonResponse
    {
        $page  = max(1, (int) $request->query('page', 1));
        $limit = min(100, max(1, (int) $request->query('limit', 20)));

        $paginated = $this->userService->getPending($page, $limit);

        return response()->json([
            'items' => UserResource::collection($paginated->items()),
            'meta'  => [
                'current_page' => $paginated->currentPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'last_page'    => $paginated->lastPage(),
            ],
        ]);
    }

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

    public function byRole(Request $request): JsonResponse
    {
        $role    = trim((string) $request->query('role', '')) ?: null;
        $perPage = min((int) $request->query('limit', 20), 100);
        $sort    = (string) $request->query('sort', 'name');
        $order   = (string) $request->query('order', 'asc');

        ['paginator' => $paginator, 'summary' => $summary] = $this->userService->getStaffByRole(
            $role, $perPage, $sort, $order, auth()->user()
        );

        return response()->json([
            'summary' => $summary,
            'items'   => StaffWithStatsResource::collection($paginator->items()),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }
}
