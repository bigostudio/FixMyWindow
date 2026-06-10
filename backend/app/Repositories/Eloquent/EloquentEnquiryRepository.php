<?php

namespace App\Repositories\Eloquent;

use App\Models\Enquiry;
use App\Repositories\Interfaces\EnquiryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentEnquiryRepository implements EnquiryRepositoryInterface
{
    public function create(array $data): Enquiry
    {
        return Enquiry::create($data);
    }

    public function findById(int $id): ?Enquiry
    {
        return Enquiry::with(['service.category', 'customer'])->find($id);
    }

    public function findByCustomer(int $customerId): Collection
    {
        return Enquiry::where('customer_id', $customerId)
                      ->with(['service.category'])
                      ->latest()
                      ->get();
    }

    public function paginateByCustomer(int $customerId, int $perPage, string $sort, string $order): LengthAwarePaginator
    {
        return Enquiry::where('customer_id', $customerId)
                      ->with(['service.category'])
                      ->orderBy($sort, $order)
                      ->paginate($perPage);
    }
}
