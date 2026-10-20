<?php

namespace App\Repositories\Eloquent;

use App\Models\BlueprintPhoto;
use App\Repositories\Interfaces\BlueprintPhotoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentBlueprintPhotoRepository implements BlueprintPhotoRepositoryInterface
{
    public function create(array $data): BlueprintPhoto
    {
        return BlueprintPhoto::create($data);
    }

    public function findById(int $id): ?BlueprintPhoto
    {
        return BlueprintPhoto::find($id);
    }

    public function findByEnquiryId(int $enquiryId): Collection
    {
        return BlueprintPhoto::where('enquiry_id', $enquiryId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function delete(BlueprintPhoto $photo): void
    {
        $photo->delete();
    }
}
