<?php

namespace App\Repositories\Interfaces;

use App\Models\Enquiry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EnquiryRepositoryInterface
{
    public function create(array $data): Enquiry;

    public function findById(int $id): ?Enquiry;

    public function findByIdWithRelations(int $id): ?Enquiry;

    public function findByCustomer(int $customerId): \Illuminate\Database\Eloquent\Collection;

    public function paginateByCustomer(int $customerId, int $perPage, string $sort, string $order): LengthAwarePaginator;

    public function paginateAll(int $perPage, string $sort, string $order): LengthAwarePaginator;

    public function update(Enquiry $enquiry, array $data): Enquiry;

    public function getDistinctAddressesByCustomer(int $customerId): \Illuminate\Support\Collection;
}
