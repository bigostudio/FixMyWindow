<?php

namespace App\Repositories\Interfaces;

use App\Models\Customer;

interface CustomerRepositoryInterface
{
    public function findByPhone(string $phone): ?Customer;

    public function upsertByPhone(string $phone): Customer;

    public function update(Customer $customer, array $data): Customer;

    public function findByEmail(string $email): ?Customer;

    public function createB2B(array $data): Customer;
}
