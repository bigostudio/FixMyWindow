<?php

namespace App\Repositories\Interfaces;

use App\Models\Service;

interface ServiceRepositoryInterface
{
    public function findActiveById(int $id): ?Service;
}
