<?php

namespace App\Repositories\Eloquent;

use App\Models\Measurement;
use App\Repositories\Interfaces\MeasurementRepositoryInterface;

class EloquentMeasurementRepository implements MeasurementRepositoryInterface
{
    public function findById(int $id): ?Measurement
    {
        return Measurement::find($id);
    }

    public function findByEnquiryId(int $enquiryId): ?Measurement
    {
        return Measurement::where('enquiry_id', $enquiryId)->first();
    }

    public function create(array $data): Measurement
    {
        return Measurement::create($data);
    }

    public function update(Measurement $measurement, array $data): Measurement
    {
        $measurement->update($data);
        return $measurement->fresh();
    }
}
