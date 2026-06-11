<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Repositories\Interfaces\ProjectAssignmentRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentProjectAssignmentRepository implements ProjectAssignmentRepositoryInterface
{
    public function findProject(int $id): ?Project
    {
        return Project::find($id);
    }

    public function getTeam(int $projectId): Collection
    {
        return ProjectAssignment::with('user')
            ->where('project_id', $projectId)
            ->orderBy('created_at')
            ->get();
    }

    public function findAssignment(int $assignmentId): ?ProjectAssignment
    {
        return ProjectAssignment::find($assignmentId);
    }

    public function existsForUser(int $projectId, int $userId): bool
    {
        return ProjectAssignment::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function create(array $data): ProjectAssignment
    {
        return ProjectAssignment::create($data);
    }

    public function delete(ProjectAssignment $assignment): void
    {
        $assignment->delete();
    }
}
