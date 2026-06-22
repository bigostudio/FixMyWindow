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
}
