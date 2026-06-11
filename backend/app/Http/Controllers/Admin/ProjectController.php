<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignProjectStaffRequest;
use App\Http\Resources\ProjectAssignmentResource;
use App\Services\ProjectAssignmentService;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectAssignmentService $service,
    ) {}

    public function assignStaff(AssignProjectStaffRequest $request, int $id): JsonResponse
    {
        $assignments = $this->service->assignStaff(
            projectId:  $id,
            userIds:    $request->validated('user_ids'),
            assignedBy: auth()->id(),
        );

        return response()->json([
            'data'    => ProjectAssignmentResource::collection($assignments),
            'message' => 'Staff assigned to project successfully.',
        ], 201);
    }

    public function removeStaff(int $id, int $tid): JsonResponse
    {
        $this->service->removeAssignment(
            projectId:    $id,
            assignmentId: $tid,
        );

        return response()->json([
            'data'    => null,
            'message' => 'Assignment removed successfully.',
        ]);
    }

    public function team(int $id): JsonResponse
    {
        $assignments = $this->service->getTeam($id);

        return response()->json([
            'data'    => ProjectAssignmentResource::collection($assignments),
            'message' => 'OK',
        ]);
    }
}
