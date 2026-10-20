<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignProjectStaffRequest;
use App\Http\Resources\ProjectAssignmentResource;
use App\Http\Resources\ProjectResource;
use App\Services\ProjectAssignmentService;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService           $projectService,
        private readonly ProjectAssignmentService $assignmentService,
    ) {}

    public function statuses(): JsonResponse
    {
        return response()->json([
            'data'    => $this->projectService->getStatuses(),
            'message' => 'OK',
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('limit', 20), 100);
        $sort    = $request->query('sort', 'created_at');
        $order   = $request->query('order', 'desc');

        $paginator = $this->projectService->list($perPage, $sort, $order);

        return response()->json([
            'data' => [
                'items' => ProjectResource::collection($paginator->items()),
                'meta'  => [
                    'current_page' => $paginator->currentPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'last_page'    => $paginator->lastPage(),
                ],
            ],
            'message' => 'OK',
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $project = $this->projectService->show($id);

        return response()->json([
            'data'    => new ProjectResource($project),
            'message' => 'OK',
        ]);
    }

    public function assignStaff(AssignProjectStaffRequest $request, int $id): JsonResponse
    {
        $assignments = $this->assignmentService->assignStaff(
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
        $this->assignmentService->removeAssignment(
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
        $assignments = $this->assignmentService->getTeam($id);

        return response()->json([
            'data'    => ProjectAssignmentResource::collection($assignments),
            'message' => 'OK',
        ]);
    }
}
