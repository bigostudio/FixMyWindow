<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'summary'         => $this->dashboardService->getSummary(),
            'pipeline'        => $this->dashboardService->getPipeline(),
            'surveys'         => $this->dashboardService->getSurveys(),
            'breakdown'       => $this->dashboardService->getBreakdown(),
            'staff'           => $this->dashboardService->getStaff(),
            'recent_activity' => $this->dashboardService->getRecentActivity(),
            'trends'          => $this->dashboardService->getTrends(),
        ]);
    }
}
