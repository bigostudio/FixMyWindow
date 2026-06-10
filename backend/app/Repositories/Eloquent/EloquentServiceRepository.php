<?php

namespace App\Repositories\Eloquent;

use App\Models\Service;
use App\Repositories\Interfaces\ServiceRepositoryInterface;

class EloquentServiceRepository implements ServiceRepositoryInterface
{
    public function findActiveById(int $id): ?Service
    {
        return Service::where('id', $id)
                      ->where('is_active', true)
                      ->first();
    }
}
