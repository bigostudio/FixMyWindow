<?php

namespace App\Repositories\Interfaces;

use App\Models\OtpRequest;

interface OtpRepositoryInterface
{
    public function create(array $data): OtpRequest;

    public function findLatestUnconsumed(string $phone): ?OtpRequest;

    public function countLastHour(string $phone): int;

    public function findLockedRecord(string $phone): ?OtpRequest;
}
