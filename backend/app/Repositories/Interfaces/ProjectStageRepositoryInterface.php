<?php

namespace App\Repositories\Interfaces;

use App\Models\ProjectStage;

interface ProjectStageRepositoryInterface
{
    public function findById(int $id): ?ProjectStage;

    public function findByEnquiryId(int $enquiryId): ?ProjectStage;

    public function create(array $data): ProjectStage;

    public function update(ProjectStage $projectStage, array $data): ProjectStage;

    public function delete(ProjectStage $projectStage): void;
}
