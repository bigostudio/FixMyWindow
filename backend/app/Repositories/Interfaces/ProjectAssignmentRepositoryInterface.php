<?php

namespace App\Repositories\Interfaces;

use App\Models\Project;
use App\Models\ProjectAssignment;
use Illuminate\Support\Collection;

interface ProjectAssignmentRepositoryInterface
{
    public function findProject(int $id): ?Project;

    public function getTeam(int $projectId): Collection;

    public function findAssignment(int $assignmentId): ?ProjectAssignment;

    public function existsForUser(int $projectId, int $userId): bool;

    public function create(array $data): ProjectAssignment;

    public function delete(ProjectAssignment $assignment): void;
}
