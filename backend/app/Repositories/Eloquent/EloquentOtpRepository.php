<?php

namespace App\Repositories\Eloquent;

use App\Models\OtpRequest;
use App\Repositories\Interfaces\OtpRepositoryInterface;

class EloquentOtpRepository implements OtpRepositoryInterface
{
    public function create(array $data): OtpRequest
    {
        return OtpRequest::create($data);
    }

    public function findLatestUnconsumed(string $phone): ?OtpRequest
    {
        return OtpRequest::where('phone', $phone)
            ->whereNull('consumed_at')
            ->latest('created_at')
            ->first();
    }

    public function countLastHour(string $phone): int
    {
        return OtpRequest::where('phone', $phone)
            ->where('created_at', '>', now()->subMinute())
            ->count();
    }

    public function findLockedRecord(string $phone): ?OtpRequest
    {
        return OtpRequest::where('phone', $phone)
            ->whereNotNull('locked_until')
            ->where('locked_until', '>', now())
            ->latest('created_at')
            ->first();
    }
}
