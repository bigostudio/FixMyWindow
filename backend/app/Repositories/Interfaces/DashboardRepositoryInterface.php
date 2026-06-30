<?php

namespace App\Repositories\Interfaces;

interface DashboardRepositoryInterface
{
    public function getEnquirySummary(): object;

    public function getCustomerSummary(): object;

    public function getRevenueSummary(): object;

    public function getContactUnreadCount(): int;

    public function getEnquiryPipeline(): array;

    public function getSurveySummary(): object;

    public function getEnquiryBreakdown(): object;

    public function getTopCities(int $limit = 10): array;

    public function getStaffSummary(): array;

    public function getRecentActivity(int $limit = 10): array;

    public function getEnquiryTrends(int $months = 6): array;
}
