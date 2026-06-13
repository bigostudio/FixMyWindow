<?php

namespace App\Repositories\Eloquent;

use App\Models\Blueprint;
use App\Models\Enquiry;
use App\Repositories\Interfaces\BlueprintRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentBlueprintRepository implements BlueprintRepositoryInterface
{
    public function findById(int $id): ?Blueprint
    {
        return Blueprint::find($id);
    }

    public function findByEnquiryId(int $enquiryId): ?Blueprint
    {
        $blueprintId = Enquiry::where('id', $enquiryId)->value('blueprint_id');

        if (! $blueprintId) {
            return null;
        }

        return Blueprint::find($blueprintId);
    }

    public function findByCustomerId(int $customerId): Collection
    {
        return Blueprint::whereHas('enquiry', fn ($q) => $q->where('customer_id', $customerId))
            ->with(['enquiry' => fn ($q) => $q->select('id', 'enquiry_number', 'customer_id', 'blueprint_id')])
            ->get();
    }

    public function create(array $data): Blueprint
    {
        return Blueprint::create($data);
    }

    public function update(Blueprint $blueprint, array $data): Blueprint
    {
        $blueprint->update($data);
        return $blueprint->fresh();
    }
}
