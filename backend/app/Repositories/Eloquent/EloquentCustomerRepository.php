<?php

namespace App\Repositories\Eloquent;

use App\Models\Customer;
use App\Repositories\Interfaces\CustomerRepositoryInterface;
use App\Support\Enums\CustomerType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentCustomerRepository implements CustomerRepositoryInterface
{
    public function findByPhone(string $phone): ?Customer
    {
        return Customer::where('phone', $phone)->first();
    }

    public function upsertByPhone(string $phone): Customer
    {
        return Customer::firstOrCreate(['phone' => $phone]);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->fill($data)->save();
        return $customer->fresh();
    }

    public function findByEmail(string $email): ?Customer
    {
        return Customer::where('email', $email)->first();
    }

    public function createByPhone(string $phone, string $name): Customer
    {
        return Customer::create(['phone' => $phone, 'name' => $name, 'type' => CustomerType::B2C]);
    }

    public function createB2B(array $data): Customer
    {
        return Customer::create($data);
    }

    public function paginateAllWithBookings(int $perPage, string $sort, string $order): LengthAwarePaginator
    {
        return Customer::with(['enquiries' => fn ($q) => $q->with('service')->latest()])
                      ->withCount('enquiries')
                      ->orderBy($sort, $order)
                      ->paginate($perPage);
    }
}
