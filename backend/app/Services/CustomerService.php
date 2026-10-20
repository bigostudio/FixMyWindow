<?php

namespace App\Services;

use App\Repositories\Interfaces\CustomerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerService
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {}

    public function listWithBookings(int $perPage, string $sort, string $order): LengthAwarePaginator
    {
        $sort  = in_array($sort, ['created_at', 'name'], true) ? $sort : 'created_at';
        $order = in_array($order, ['asc', 'desc'], true) ? $order : 'desc';

        return $this->customerRepository->paginateAllWithBookings($perPage, $sort, $order);
    }
}
