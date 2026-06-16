<?php

namespace App\Services;

use App\Models\ProjectStage;
use App\Repositories\Interfaces\EnquiryRepositoryInterface;
use App\Repositories\Interfaces\ProjectStageRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProjectStageService
{
    public function __construct(
        private readonly ProjectStageRepositoryInterface $projectStageRepo,
        private readonly EnquiryRepositoryInterface       $enquiryRepo,
    ) {}

    public function create(array $data): ProjectStage
    {
        $enquiry = $this->enquiryRepo->findById($data['enquiry_id']);

        if (! $enquiry) {
            throw new NotFoundHttpException('Enquiry not found.');
        }

        return $this->projectStageRepo->create([
            'enquiry_id' => $data['enquiry_id'],
            'towers'     => $data['towers'],
        ]);
    }

    public function findById(int $id): ProjectStage
    {
        $projectStage = $this->projectStageRepo->findById($id);

        if (! $projectStage) {
            throw new NotFoundHttpException('Project stage not found.');
        }

        return $projectStage;
    }

    public function update(int $id, array $data): ProjectStage
    {
        $projectStage = $this->projectStageRepo->findById($id);

        if (! $projectStage) {
            throw new NotFoundHttpException('Project stage not found.');
        }

        return $this->projectStageRepo->update($projectStage, $data);
    }

    public function delete(int $id): void
    {
        $projectStage = $this->projectStageRepo->findById($id);

        if (! $projectStage) {
            throw new NotFoundHttpException('Project stage not found.');
        }

        $this->projectStageRepo->delete($projectStage);
    }
}
