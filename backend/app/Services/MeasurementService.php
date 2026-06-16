<?php

namespace App\Services;

use App\Models\Measurement;
use App\Repositories\Interfaces\EnquiryRepositoryInterface;
use App\Repositories\Interfaces\MeasurementRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MeasurementService
{
    public function __construct(
        private readonly MeasurementRepositoryInterface $measurementRepo,
        private readonly EnquiryRepositoryInterface     $enquiryRepo,
    ) {}

    public function create(array $data): Measurement
    {
        $enquiry = $this->enquiryRepo->findById($data['enquiry_id']);

        if (! $enquiry) {
            throw new NotFoundHttpException('Enquiry not found.');
        }

        return $this->measurementRepo->create([
            'enquiry_id' => $data['enquiry_id'],
            'towers'     => $data['towers'],
        ]);
    }

    public function findById(int $id): Measurement
    {
        $measurement = $this->measurementRepo->findById($id);

        if (! $measurement) {
            throw new NotFoundHttpException('Measurement not found.');
        }

        return $measurement;
    }

    public function update(int $id, array $data): Measurement
    {
        $measurement = $this->measurementRepo->findById($id);

        if (! $measurement) {
            throw new NotFoundHttpException('Measurement not found.');
        }

        return $this->measurementRepo->update($measurement, $data);
    }
}
