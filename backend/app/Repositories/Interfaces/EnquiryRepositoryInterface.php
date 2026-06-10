<?php

namespace App\Repositories\Interfaces;

use App\Models\Enquiry;

interface EnquiryRepositoryInterface
{
    public function create(array $data): Enquiry;

    public function findById(int $id): ?Enquiry;

    public function findByCustomer(int $customerId): \Illuminate\Database\Eloquent\Collection;
}
