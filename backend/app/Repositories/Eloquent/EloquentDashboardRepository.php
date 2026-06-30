<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\DashboardRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentDashboardRepository implements DashboardRepositoryInterface
{
    public function getEnquirySummary(): object
    {
        return DB::selectOne("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS today,
                SUM(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) THEN 1 ELSE 0 END) AS this_week,
                SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) THEN 1 ELSE 0 END) AS this_month
            FROM enquiries
            WHERE enquiry_number IS NOT NULL
        ");
    }

    public function getCustomerSummary(): object
    {
        return DB::selectOne("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN type = 'b2c' THEN 1 ELSE 0 END) AS b2c,
                SUM(CASE WHEN type = 'b2b' THEN 1 ELSE 0 END) AS b2b
            FROM customers
        ");
    }

    public function getRevenueSummary(): object
    {
        return DB::selectOne("
            SELECT
                COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN payment_amount ELSE 0 END), 0) AS collected,
                SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN payment_amount ELSE 0 END), 0) AS pending_amount
            FROM enquiries
            WHERE enquiry_number IS NOT NULL
        ");
    }

    public function getContactUnreadCount(): int
    {
        return (int) DB::table('contact_submissions')
            ->where('status', 'new')
            ->count();
    }

    public function getEnquiryPipeline(): array
    {
        return DB::select("
            SELECT
                e.status,
                COALESCE(ps.label, e.status) AS label,
                COALESCE(ps.sort_order, 999) AS sort_order,
                COUNT(*) AS count
            FROM enquiries e
            LEFT JOIN project_statuses ps ON ps.code = e.status
            WHERE e.enquiry_number IS NOT NULL
            GROUP BY e.status, ps.label, ps.sort_order
            ORDER BY COALESCE(ps.sort_order, 999)
        ");
    }

    public function getSurveySummary(): object
    {
        return DB::selectOne("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN outcome = 'go' THEN 1 ELSE 0 END) AS go_count,
                SUM(CASE WHEN outcome = 'hold' THEN 1 ELSE 0 END) AS hold_count,
                SUM(CASE WHEN outcome = 'no_go' THEN 1 ELSE 0 END) AS no_go_count,
                SUM(CASE WHEN outcome IS NULL THEN 1 ELSE 0 END) AS pending_count
            FROM surveys
        ");
    }

    public function getEnquiryBreakdown(): object
    {
        return DB::selectOne("
            SELECT
                SUM(CASE WHEN type = 'b2c' THEN 1 ELSE 0 END) AS b2c,
                SUM(CASE WHEN type = 'b2b' THEN 1 ELSE 0 END) AS b2b,
                SUM(CASE WHEN material_type = 'upvc' THEN 1 ELSE 0 END) AS upvc,
                SUM(CASE WHEN material_type = 'aluminium' THEN 1 ELSE 0 END) AS aluminium,
                SUM(CASE WHEN material_type = 'facade' THEN 1 ELSE 0 END) AS facade,
                SUM(CASE WHEN property_type = 'home' THEN 1 ELSE 0 END) AS home,
                SUM(CASE WHEN property_type = 'Highrise' THEN 1 ELSE 0 END) AS highrise
            FROM enquiries
            WHERE enquiry_number IS NOT NULL
        ");
    }

    public function getTopCities(int $limit = 10): array
    {
        return DB::select("
            SELECT city, COUNT(*) AS count
            FROM enquiries
            WHERE enquiry_number IS NOT NULL
            GROUP BY city
            ORDER BY count DESC
            LIMIT ?
        ", [$limit]);
    }

    public function getStaffSummary(): array
    {
        $totals = DB::selectOne("
            SELECT
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS total_active,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS pending_approvals
            FROM users
            WHERE deleted_at IS NULL
        ");

        $byRole = DB::select("
            SELECT
                u.role,
                COUNT(DISTINCT u.id) AS count,
                COUNT(DISTINCT CASE
                    WHEN e.status NOT IN ('handovered', 'cancelled')
                    AND e.enquiry_number IS NOT NULL
                    THEN e.id
                END) AS active_assignments
            FROM users u
            LEFT JOIN project_assignments pa ON pa.user_id = u.id
            LEFT JOIN enquiries e ON e.id = pa.enquiry_id
            WHERE u.is_active = 1 AND u.deleted_at IS NULL
            GROUP BY u.role
            ORDER BY u.role
        ");

        return ['totals' => $totals, 'by_role' => $byRole];
    }

    public function getRecentActivity(int $limit = 10): array
    {
        return DB::select("
            SELECT pt.status, pt.actor_name, pt.created_at, e.enquiry_number
            FROM project_timeline pt
            LEFT JOIN enquiries e ON e.id = pt.enquiry_id
            ORDER BY pt.created_at DESC
            LIMIT ?
        ", [$limit]);
    }

    public function getEnquiryTrends(int $months = 6): array
    {
        return DB::select("
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS count
            FROM enquiries
            WHERE enquiry_number IS NOT NULL
              AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY month
            ORDER BY month ASC
        ", [$months]);
    }
}
