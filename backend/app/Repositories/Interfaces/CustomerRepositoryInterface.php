<?php

namespace App\Repositories\Interfaces;

use App\Models\Customer;

interface CustomerRepositoryInterface
{
    public function findByPhone(string $phone): ?Customer;

    public function upsertByPhone(string $phone): Customer;
}
