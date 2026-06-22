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
}
