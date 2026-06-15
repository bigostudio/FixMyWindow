<?php

namespace App\Repositories\Eloquent;

use App\Models\Blueprint;
use App\Models\Enquiry;
use App\Repositories\Interfaces\BlueprintRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
        return Blueprint::where('customer_id', $customerId)->get();
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

    public function delete(Blueprint $blueprint): void
    {
        DB::transaction(function () use ($blueprint) {
            Enquiry::where('blueprint_id', $blueprint->id)
                ->update(['blueprint_id' => null]);

            $blueprint->delete();
        });
    }
}
