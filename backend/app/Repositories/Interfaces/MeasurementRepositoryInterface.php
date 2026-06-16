<?php

namespace App\Repositories\Interfaces;

use App\Models\Measurement;

interface MeasurementRepositoryInterface
{
    public function findById(int $id): ?Measurement;

    public function findByEnquiryId(int $enquiryId): ?Measurement;

    public function create(array $data): Measurement;

    public function update(Measurement $measurement, array $data): Measurement;
}
