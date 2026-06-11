<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Repositories\Interfaces\ProjectAssignmentRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Support\Enums\Role;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProjectAssignmentService
{
    private const ASSIGNABLE_ROLES = [
        Role::OperationsManager->value,
        Role::Supervisor->value,
        Role::Technician->value,
    ];

    public function __construct(
        private readonly ProjectAssignmentRepositoryInterface $assignmentRepo,
        private readonly UserRepositoryInterface $userRepo,
    ) {}

    public function assignStaff(int $projectId, array $userIds, int $assignedBy): Collection
    {
        $project = $this->assignmentRepo->findProject($projectId);
        if (! $project) {
            throw new NotFoundHttpException('Project not found.');
        }

        $assigned = collect();

        foreach ($userIds as $userId) {
            $user = $this->userRepo->findById($userId);

            if (! $user) {
                throw new BusinessRuleException("User {$userId} not found.");
            }

            if (! in_array($user->role->value, self::ASSIGNABLE_ROLES, true)) {
                throw new BusinessRuleException(
                    "User {$user->name} has role '{$user->role->value}' which cannot be assigned to a project. Allowed roles: ops_manager, supervisor, technician."
                );
            }

            if ($this->assignmentRepo->existsForUser($projectId, $userId)) {
                throw new BusinessRuleException("User {$user->name} is already assigned to this project.");
            }

            $assignment = $this->assignmentRepo->create([
                'project_id'  => $projectId,
                'user_id'     => $userId,
                'role'        => $user->role->value,
                'assigned_by' => $assignedBy,
            ]);

            $assignment->setRelation('user', $user);
            $assigned->push($assignment);
        }

        return $assigned;
    }

    public function removeAssignment(int $projectId, int $assignmentId): void
    {
        $project = $this->assignmentRepo->findProject($projectId);
        if (! $project) {
            throw new NotFoundHttpException('Project not found.');
        }

        $assignment = $this->assignmentRepo->findAssignment($assignmentId);
        if (! $assignment || $assignment->project_id !== $projectId) {
            throw new NotFoundHttpException('Assignment not found.');
        }

        $this->assignmentRepo->delete($assignment);
    }

    public function getTeam(int $projectId): Collection
    {
        $project = $this->assignmentRepo->findProject($projectId);
        if (! $project) {
            throw new NotFoundHttpException('Project not found.');
        }

        return $this->assignmentRepo->getTeam($projectId);
    }
}
