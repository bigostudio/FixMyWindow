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
}
