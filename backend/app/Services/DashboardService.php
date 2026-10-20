<?php

namespace App\Services;

use App\Repositories\Interfaces\DashboardRepositoryInterface;
use App\Support\Enums\TimelineStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    private const BUSINESS_TIMEZONE = 'Asia/Kolkata';
    private const CACHE_TTL_SECONDS = 60;
    private const CACHE_KEY = 'admin_dashboard:v2';

    // SLA thresholds used to derive urgent_requests priority — tune here.
    private const URGENT_SLA_HIGH_HOURS = 24;
    private const URGENT_SLA_URGENT_HOURS = 72;

    private const MAX_ACTIVITY_LIMIT = 50;
    private const MAX_URGENT_LIMIT = 20;

    public function __construct(
        private readonly DashboardRepositoryInterface $dashboardRepository,
    ) {}

    public function getDashboard(int $activityLimit, int $urgentLimit): array
    {
        $raw = Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, fn () => $this->buildRawDashboard());

        return [
            'total_requests'   => $raw['total_requests'],
            'pending'          => $raw['pending'],
            'in_progress'      => $raw['in_progress'],
            'completed'        => $raw['completed'],
            'cancelled'        => $raw['cancelled'],
            'revenue'          => $raw['revenue'],
            'weekly_revenue'   => $raw['weekly_revenue'],
            'recent_activity'  => array_slice($raw['recent_activity'], 0, $activityLimit),
            'team_overview'    => $raw['team_overview'],
            'urgent_requests'  => array_slice($raw['urgent_requests'], 0, $urgentLimit),
        ];
    }

    private function buildRawDashboard(): array
    {
        $now         = Carbon::now(self::BUSINESS_TIMEZONE);
        $todayStart  = $now->copy()->startOfDay()->utc();
        $yesterdayStart = $now->copy()->startOfDay()->subDay()->utc();
        $weekStart   = $now->copy()->subDays(7)->utc();
        $prevWeekStart = $now->copy()->subDays(14)->utc();
        $monthStart  = $now->copy()->subDays(30)->utc();
        $prevMonthStart = $now->copy()->subDays(60)->utc();

        $requestCounts = $this->dashboardRepository->getRequestCounts($todayStart, $weekStart, $monthStart);
        $statusBuckets = $this->dashboardRepository->getStatusBucketCounts();
        $revenue       = $this->dashboardRepository->getRevenueMetrics(
            $todayStart, $yesterdayStart, $weekStart, $prevWeekStart, $monthStart, $prevMonthStart,
        );

        return [
            'total_requests' => [
                'today' => (int) $requestCounts->today,
                'week'  => (int) $requestCounts->week,
                'month' => (int) $requestCounts->month,
            ],
            'pending'     => (int) $statusBuckets->pending,
            'in_progress' => (int) $statusBuckets->in_progress,
            'completed'   => (int) $statusBuckets->completed,
            'cancelled'   => (int) $statusBuckets->cancelled,
            'revenue' => [
                'today'            => (int) $revenue->today,
                'week'             => (int) $revenue->week,
                'month'            => (int) $revenue->month,
                'today_trend_pct'  => $this->trendPct((int) $revenue->today, (int) $revenue->yesterday),
                'week_trend_pct'   => $this->trendPct((int) $revenue->week, (int) $revenue->prev_week),
                'month_trend_pct'  => $this->trendPct((int) $revenue->month, (int) $revenue->prev_month),
            ],
            'weekly_revenue'  => $this->buildWeeklyRevenue($now),
            'recent_activity' => $this->buildRecentActivity(self::MAX_ACTIVITY_LIMIT),
            'team_overview'   => $this->buildTeamOverview(),
            'urgent_requests' => $this->buildUrgentRequests($now, self::MAX_URGENT_LIMIT),
        ];
    }

    private function trendPct(int $current, int $previous): int
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }

    private function buildWeeklyRevenue(Carbon $now): array
    {
        $rangeStart = $now->copy()->subDays(6)->startOfDay()->utc();
        $rows       = $this->dashboardRepository->getPaidRevenueRows($rangeStart);

        $amountsByDate = [];
        foreach ($rows as $row) {
            $date = Carbon::parse($row->paid_at, 'UTC')->setTimezone(self::BUSINESS_TIMEZONE)->toDateString();
            $amountsByDate[$date] = ($amountsByDate[$date] ?? 0) + (int) $row->payment_amount;
        }

        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $days[] = [
                'date'      => $date->toDateString(),
                'day_label' => $date->format('D'),
                'amount'    => $amountsByDate[$date->toDateString()] ?? 0,
            ];
        }

        return $days;
    }

    private function buildRecentActivity(int $limit): array
    {
        $rows = $this->dashboardRepository->getRecentActivity($limit);

        return array_map(function ($row) {
            $type = match ($row->status) {
                TimelineStatus::New->value => 'request_created',
                TimelineStatus::TeamAssigned->value => 'technician_assigned',
                TimelineStatus::PaymentReceived->value => 'payment_received',
                default => 'status_updated',
            };

            $message = match ($type) {
                'request_created' => "New request from {$row->customer_name} for {$row->service_name}",
                default => $row->description ?: "{$row->status} for {$row->enquiry_number}",
            };

            return [
                'id'         => "ACT{$row->id}",
                'type'       => $type,
                'message'    => $message,
                'timestamp'  => Carbon::parse($row->created_at, 'UTC')->format('Y-m-d\TH:i:s\Z'),
                'request_id' => $row->enquiry_number,
            ];
        }, $rows);
    }

    private function buildTeamOverview(): array
    {
        $row = $this->dashboardRepository->getTeamOverview();

        return [
            'operations_managers' => (int) $row->operations_managers,
            'supervisors'          => (int) $row->supervisors,
            'technicians'          => (int) $row->technicians,
            'customers'            => (int) $row->customers,
        ];
    }

    private function buildUrgentRequests(Carbon $now, int $limit): array
    {
        $highBefore   = $now->copy()->subHours(self::URGENT_SLA_HIGH_HOURS)->utc();
        $urgentBefore = $now->copy()->subHours(self::URGENT_SLA_URGENT_HOURS)->utc();

        $rows = $this->dashboardRepository->getUrgentRequests($highBefore, $urgentBefore, $limit);

        return array_map(fn ($row) => [
            'id'            => $row->enquiry_number,
            'customer_name' => $row->customer_name,
            'service_type'  => $row->service_type,
            'priority'      => $row->priority,
        ], $rows);
    }
}
