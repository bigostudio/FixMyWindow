<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DashboardQueryRequest;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function index(DashboardQueryRequest $request): JsonResponse
    {
        $activityLimit = (int) ($request->validated('activity_limit') ?? 10);
        $urgentLimit   = (int) ($request->validated('urgent_limit') ?? 4);

        return response()->json(
            $this->dashboardService->getDashboard($activityLimit, $urgentLimit)
        );
    }
}
