<?php

namespace App\Repositories\Eloquent;

use App\Models\Enquiry;
use App\Repositories\Interfaces\EnquiryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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

    public function findByIdWithRelations(int $id): ?Enquiry
    {
        return Enquiry::with([
            'service.category',
            'customer',
            'assignments.user',
            'timeline',
            'survey',
            'projectStage',
        ])->find($id);
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

    public function paginateAll(int $perPage, string $sort, string $order): LengthAwarePaginator
    {
        return Enquiry::with(['customer', 'service', 'assignments.user'])
                      ->whereNotNull('enquiry_number')
                      ->orderBy($sort, $order)
                      ->paginate($perPage);
    }

    public function paginateForUser(int $userId, int $perPage, string $sort, string $order): LengthAwarePaginator
    {
        return Enquiry::with(['customer', 'service', 'assignments.user'])
                      ->whereNotNull('enquiry_number')
                      ->whereHas('assignments', fn ($q) => $q->where('user_id', $userId))
                      ->orderBy($sort, $order)
                      ->paginate($perPage);
    }

    public function update(Enquiry $enquiry, array $data): Enquiry
    {
        $enquiry->update($data);
        return $enquiry->fresh();
    }

    public function getDistinctAddressesByCustomer(int $customerId): \Illuminate\Support\Collection
    {
        return Enquiry::where('customer_id', $customerId)
            ->select(
                'address',
                DB::raw('MIN(city) as city'),
                DB::raw('MIN(latitude) as latitude'),
                DB::raw('MIN(longitude) as longitude'),
            )
            ->groupBy('address')
            ->orderByRaw('MAX(created_at) DESC')
            ->get();
    }
}
