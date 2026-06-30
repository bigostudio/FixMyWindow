<?php

namespace App\Repositories\Interfaces;

use App\Models\BlueprintPhoto;
use Illuminate\Database\Eloquent\Collection;

interface BlueprintPhotoRepositoryInterface
{
    public function create(array $data): BlueprintPhoto;

    public function findById(int $id): ?BlueprintPhoto;

    public function findByEnquiryId(int $enquiryId): Collection;

    public function delete(BlueprintPhoto $photo): void;
}
