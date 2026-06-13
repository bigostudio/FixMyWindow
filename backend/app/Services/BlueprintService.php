<?php

namespace App\Services;

use App\Exceptions\ConflictException;
use App\Models\Blueprint;
use App\Repositories\Interfaces\BlueprintRepositoryInterface;
use App\Repositories\Interfaces\EnquiryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BlueprintService
{
    public function __construct(
        private readonly BlueprintRepositoryInterface $blueprintRepo,
        private readonly EnquiryRepositoryInterface   $enquiryRepo,
    ) {}

    public function getByEnquiryId(int $enquiryId): Blueprint
    {
        $enquiry = $this->enquiryRepo->findById($enquiryId);

        if (! $enquiry) {
            throw new NotFoundHttpException('Enquiry not found.');
        }

        $blueprint = $this->blueprintRepo->findByEnquiryId($enquiryId);

        if (! $blueprint) {
            throw new NotFoundHttpException('No blueprint found for this enquiry.');
        }

        return $blueprint;
    }

    public function getByCustomerId(int $customerId): Collection
    {
        return $this->blueprintRepo->findByCustomerId($customerId);
    }

    public function create(int $enquiryId, array $data): Blueprint
    {
        $enquiry = $this->enquiryRepo->findById($enquiryId);

        if (! $enquiry) {
            throw new NotFoundHttpException('Enquiry not found.');
        }

        if ($enquiry->blueprint_id) {
            throw new ConflictException('A blueprint already exists for this enquiry.');
        }

        return DB::transaction(function () use ($enquiry, $data) {
            $blueprint = $this->blueprintRepo->create($data);
            $this->enquiryRepo->update($enquiry, ['blueprint_id' => $blueprint->id]);
            return $blueprint;
        });
    }

    public function update(int $enquiryId, array $data): Blueprint
    {
        $enquiry = $this->enquiryRepo->findById($enquiryId);

        if (! $enquiry) {
            throw new NotFoundHttpException('Enquiry not found.');
        }

        $blueprint = $this->blueprintRepo->findByEnquiryId($enquiryId);

        if (! $blueprint) {
            throw new NotFoundHttpException('No blueprint found for this enquiry.');
        }

        return $this->blueprintRepo->update($blueprint, $data);
    }
}
