<?php

namespace App\Repositories\Eloquent;

use App\Models\Customer;
use App\Repositories\Interfaces\CustomerRepositoryInterface;

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

    public function createB2B(array $data): Customer
    {
        return Customer::create($data);
    }
}
