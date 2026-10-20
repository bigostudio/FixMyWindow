<?php

namespace App\Repositories\Interfaces;

use Carbon\CarbonInterface;

interface DashboardRepositoryInterface
{
    public function getRequestCounts(CarbonInterface $todayStart, CarbonInterface $weekStart, CarbonInterface $monthStart): object;

    public function getStatusBucketCounts(): object;

    public function getRevenueMetrics(
        CarbonInterface $todayStart,
        CarbonInterface $yesterdayStart,
        CarbonInterface $weekStart,
        CarbonInterface $prevWeekStart,
        CarbonInterface $monthStart,
        CarbonInterface $prevMonthStart,
    ): object;

    public function getPaidRevenueRows(CarbonInterface $since): array;

    public function getRecentActivity(int $limit): array;

    public function getTeamOverview(): object;

    public function getUrgentRequests(CarbonInterface $highBefore, CarbonInterface $urgentBefore, int $limit): array;
}
