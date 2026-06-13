<?php

namespace App\Repositories\Interfaces;

use App\Models\Blueprint;
use Illuminate\Database\Eloquent\Collection;

interface BlueprintRepositoryInterface
{
    public function findById(int $id): ?Blueprint;

    public function findByEnquiryId(int $enquiryId): ?Blueprint;

    public function findByCustomerId(int $customerId): Collection;

    public function create(array $data): Blueprint;

    public function update(Blueprint $blueprint, array $data): Blueprint;
}
