<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\DashboardRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class EloquentDashboardRepository implements DashboardRepositoryInterface
{
    private const DATETIME_FORMAT = 'Y-m-d H:i:s';

    public function getRequestCounts(CarbonInterface $todayStart, CarbonInterface $weekStart, CarbonInterface $monthStart): object
    {
        return DB::selectOne("
            SELECT
                SUM(CASE WHEN created_at >= :today_start THEN 1 ELSE 0 END) AS today,
                SUM(CASE WHEN created_at >= :week_start THEN 1 ELSE 0 END) AS week,
                SUM(CASE WHEN created_at >= :month_start THEN 1 ELSE 0 END) AS month
            FROM enquiries
            WHERE status != 'draft'
        ", [
            'today_start' => $todayStart->format(self::DATETIME_FORMAT),
            'week_start'  => $weekStart->format(self::DATETIME_FORMAT),
            'month_start' => $monthStart->format(self::DATETIME_FORMAT),
        ]);
    }

    public function getStatusBucketCounts(): object
    {
        return DB::selectOne("
            SELECT
                SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status IN (
                    'survey_initiated', 'survey_completed', 'measurement_initiated', 'measurement_completed',
                    'quotation_generated', 'quotation_approved', 'installation_initiated', 'installation_completed',
                    'quality_check_initiated', 'quality_check_completed', 'on_hold'
                ) THEN 1 ELSE 0 END) AS in_progress,
                SUM(CASE WHEN status = 'handovered' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled
            FROM enquiries
            WHERE status != 'draft'
        ");
    }

    public function getRevenueMetrics(
        CarbonInterface $todayStart,
        CarbonInterface $yesterdayStart,
        CarbonInterface $weekStart,
        CarbonInterface $prevWeekStart,
        CarbonInterface $monthStart,
        CarbonInterface $prevMonthStart,
    ): object {
        return DB::selectOne("
            SELECT
                COALESCE(SUM(CASE WHEN payment_status = 'paid' AND paid_at >= :today_start THEN payment_amount ELSE 0 END), 0) AS today,
                COALESCE(SUM(CASE WHEN payment_status = 'paid' AND paid_at >= :yesterday_start AND paid_at < :today_start2 THEN payment_amount ELSE 0 END), 0) AS yesterday,
                COALESCE(SUM(CASE WHEN payment_status = 'paid' AND paid_at >= :week_start THEN payment_amount ELSE 0 END), 0) AS week,
                COALESCE(SUM(CASE WHEN payment_status = 'paid' AND paid_at >= :prev_week_start AND paid_at < :week_start2 THEN payment_amount ELSE 0 END), 0) AS prev_week,
                COALESCE(SUM(CASE WHEN payment_status = 'paid' AND paid_at >= :month_start THEN payment_amount ELSE 0 END), 0) AS month,
                COALESCE(SUM(CASE WHEN payment_status = 'paid' AND paid_at >= :prev_month_start AND paid_at < :month_start2 THEN payment_amount ELSE 0 END), 0) AS prev_month
            FROM enquiries
        ", [
            'today_start'      => $todayStart->format(self::DATETIME_FORMAT),
            'today_start2'     => $todayStart->format(self::DATETIME_FORMAT),
            'yesterday_start'  => $yesterdayStart->format(self::DATETIME_FORMAT),
            'week_start'       => $weekStart->format(self::DATETIME_FORMAT),
            'week_start2'      => $weekStart->format(self::DATETIME_FORMAT),
            'prev_week_start'  => $prevWeekStart->format(self::DATETIME_FORMAT),
            'month_start'      => $monthStart->format(self::DATETIME_FORMAT),
            'month_start2'     => $monthStart->format(self::DATETIME_FORMAT),
            'prev_month_start' => $prevMonthStart->format(self::DATETIME_FORMAT),
        ]);
    }

    public function getPaidRevenueRows(CarbonInterface $since): array
    {
        return DB::select("
            SELECT paid_at, payment_amount
            FROM enquiries
            WHERE payment_status = 'paid' AND paid_at >= ?
        ", [$since->format(self::DATETIME_FORMAT)]);
    }

    public function getRecentActivity(int $limit): array
    {
        return DB::select("
            SELECT pt.id, pt.status, pt.description, pt.actor_name, pt.created_at,
                   e.enquiry_number, c.name AS customer_name, s.name AS service_name
            FROM project_timeline pt
            LEFT JOIN enquiries e ON e.id = pt.enquiry_id
            LEFT JOIN customers c ON c.id = e.customer_id
            LEFT JOIN services s ON s.id = e.service_id
            ORDER BY pt.created_at DESC
            LIMIT " . (int) $limit);
    }

    public function getTeamOverview(): object
    {
        $staff = DB::selectOne("
            SELECT
                SUM(CASE WHEN role = 'ops_manager' THEN 1 ELSE 0 END) AS operations_managers,
                SUM(CASE WHEN role = 'supervisor' THEN 1 ELSE 0 END) AS supervisors,
                SUM(CASE WHEN role = 'technician' THEN 1 ELSE 0 END) AS technicians
            FROM users
            WHERE is_active = 1 AND deleted_at IS NULL
        ");

        $staff->customers = (int) DB::table('customers')->count();

        return $staff;
    }

    public function getUrgentRequests(CarbonInterface $highBefore, CarbonInterface $urgentBefore, int $limit): array
    {
        return DB::select("
            SELECT
                e.enquiry_number, c.name AS customer_name, s.name AS service_type,
                CASE WHEN e.created_at < :urgent_before THEN 'urgent' ELSE 'high' END AS priority
            FROM enquiries e
            LEFT JOIN customers c ON c.id = e.customer_id
            LEFT JOIN services s ON s.id = e.service_id
            WHERE e.status IN ('new', 'survey_initiated') AND e.created_at < :high_before
            ORDER BY e.created_at DESC
            LIMIT " . (int) $limit, [
            'urgent_before' => $urgentBefore->format(self::DATETIME_FORMAT),
            'high_before'   => $highBefore->format(self::DATETIME_FORMAT),
        ]);
    }
}
