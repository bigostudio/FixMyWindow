<?php

namespace App\Repositories\Eloquent;

use App\Models\ProjectStage;
use App\Repositories\Interfaces\ProjectStageRepositoryInterface;

class EloquentProjectStageRepository implements ProjectStageRepositoryInterface
{
    public function findById(int $id): ?ProjectStage
    {
        return ProjectStage::find($id);
    }

    public function findByEnquiryId(int $enquiryId): ?ProjectStage
    {
        return ProjectStage::where('enquiry_id', $enquiryId)->first();
    }

    public function create(array $data): ProjectStage
    {
        return ProjectStage::create($data);
    }

    public function update(ProjectStage $projectStage, array $data): ProjectStage
    {
        $projectStage->update($data);
        return $projectStage->fresh();
    }

    public function delete(ProjectStage $projectStage): void
    {
        $projectStage->delete();
    }
}
