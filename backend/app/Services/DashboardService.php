<?php

namespace App\Services;

use App\Repositories\Interfaces\DashboardRepositoryInterface;

class DashboardService
{
    public function __construct(
        private readonly DashboardRepositoryInterface $dashboardRepository,
    ) {}

    public function getSummary(): array
    {
        $enquiries = $this->dashboardRepository->getEnquirySummary();
        $customers = $this->dashboardRepository->getCustomerSummary();
        $revenue   = $this->dashboardRepository->getRevenueSummary();

        return [
            'enquiries' => [
                'total'      => (int) $enquiries->total,
                'today'      => (int) $enquiries->today,
                'this_week'  => (int) $enquiries->this_week,
                'this_month' => (int) $enquiries->this_month,
            ],
            'customers' => [
                'total' => (int) $customers->total,
                'b2c'   => (int) $customers->b2c,
                'b2b'   => (int) $customers->b2b,
            ],
            'revenue' => [
                'collected'     => (int) $revenue->collected,
                'pending_count' => (int) $revenue->pending_count,
                'pending_amount'=> (int) $revenue->pending_amount,
            ],
            'contact_submissions_unread' => $this->dashboardRepository->getContactUnreadCount(),
        ];
    }

    public function getPipeline(): array
    {
        return array_map(fn($row) => [
            'status' => $row->status,
            'label'  => $row->label,
            'count'  => (int) $row->count,
        ], $this->dashboardRepository->getEnquiryPipeline());
    }

    public function getSurveys(): array
    {
        $row = $this->dashboardRepository->getSurveySummary();

        return [
            'total'   => (int) $row->total,
            'go'      => (int) $row->go_count,
            'hold'    => (int) $row->hold_count,
            'no_go'   => (int) $row->no_go_count,
            'pending' => (int) $row->pending_count,
        ];
    }

    public function getBreakdown(): array
    {
        $row   = $this->dashboardRepository->getEnquiryBreakdown();
        $cities = $this->dashboardRepository->getTopCities(10);

        return [
            'by_type' => [
                'b2c' => (int) $row->b2c,
                'b2b' => (int) $row->b2b,
            ],
            'by_material' => [
                'upvc'      => (int) $row->upvc,
                'aluminium' => (int) $row->aluminium,
                'facade'    => (int) $row->facade,
            ],
            'by_property' => [
                'home'       => (int) $row->home,
                'highrise' => (int) $row->highrise,
            ],
            'by_city' => array_map(fn($c) => [
                'city'  => $c->city,
                'count' => (int) $c->count,
            ], $cities),
        ];
    }

    public function getStaff(): array
    {
        $data = $this->dashboardRepository->getStaffSummary();

        return [
            'total_active'      => (int) $data['totals']->total_active,
            'pending_approvals' => (int) $data['totals']->pending_approvals,
            'by_role'           => array_map(fn($r) => [
                'role'               => $r->role,
                'count'              => (int) $r->count,
                'active_assignments' => (int) $r->active_assignments,
            ], $data['by_role']),
        ];
    }

    public function getRecentActivity(): array
    {
        return array_map(fn($row) => [
            'enquiry_number' => $row->enquiry_number,
            'event'          => $row->status,
            'actor_name'     => $row->actor_name,
            'occurred_at'    => $row->created_at,
        ], $this->dashboardRepository->getRecentActivity(10));
    }

    public function getTrends(): array
    {
        return [
            'enquiries_by_month' => array_map(fn($row) => [
                'month' => $row->month,
                'count' => (int) $row->count,
            ], $this->dashboardRepository->getEnquiryTrends(6)),
        ];
    }
}
