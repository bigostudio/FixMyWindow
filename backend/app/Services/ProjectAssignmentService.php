<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Repositories\Interfaces\ProjectAssignmentRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProjectAssignmentService
{
    public function __construct(
        private readonly ProjectAssignmentRepositoryInterface $assignmentRepo,
        private readonly UserRepositoryInterface $userRepo,
    ) {}

    /**
     * Full replace: accepts a section-keyed map of user ID arrays.
     * Clears all existing assignments for the enquiry then inserts the new set.
     *
     * @param  array<string, int[]>  $sections  e.g. ['ops_manager' => [1,2], 'survey' => [5]]
     */
    public function assignStaff(int $enquiryId, array $sections, int $assignedBy): array
    {
        $enquiry = $this->assignmentRepo->findEnquiry($enquiryId);
        if (! $enquiry) {
            throw new NotFoundHttpException('Enquiry not found.');
        }

        $now     = now()->toDateTimeString();
        $records = [];

        foreach ($sections as $sectionValue => $userIds) {
            if (empty($userIds)) {
                continue;
            }

            foreach ($userIds as $userId) {
                $user = $this->userRepo->findById($userId);

                if (! $user) {
                    throw new BusinessRuleException("User ID {$userId} not found.");
                }

                $records[] = [
                    'enquiry_id'         => $enquiryId,
                    'user_id'            => $userId,
                    'role'               => $user->role->value,
                    'assignment_section' => $sectionValue,
                    'assigned_by'        => $assignedBy,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ];
            }
        }

        $this->assignmentRepo->replaceAll($enquiryId, $records);

        return $this->assignmentRepo->getTeamGrouped($enquiryId);
    }

    public function getTeam(int $enquiryId): array
    {
        $enquiry = $this->assignmentRepo->findEnquiry($enquiryId);
        if (! $enquiry) {
            throw new NotFoundHttpException('Enquiry not found.');
        }

        return $this->assignmentRepo->getTeamGrouped($enquiryId);
    }
}
