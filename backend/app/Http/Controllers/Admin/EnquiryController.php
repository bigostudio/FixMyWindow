<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignProjectStaffRequest;
use App\Http\Requests\Admin\InitiateSurveyRequest;
use App\Http\Requests\Admin\UpdateEnquiryStatusRequest;
use App\Http\Resources\AdminBookingResource;
use App\Http\Resources\AdminEnquiryDetailResource;
use App\Http\Resources\SurveyResource;
use App\Services\EnquiryService;
use App\Services\ProjectAssignmentService;
use App\Services\SurveyService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnquiryController extends Controller
{
    public function __construct(
        private readonly EnquiryService           $enquiryService,
        private readonly ProjectAssignmentService $assignmentService,
        private readonly SurveyService            $surveyService,
    ) {}

    public function statuses(): JsonResponse
    {
        return response()->json([
            'data'    => $this->enquiryService->getStatuses(),
            'message' => 'OK',
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('limit', 20), 100);
        $sort    = $request->query('sort', 'created_at');
        $order   = $request->query('order', 'desc');

        $paginator = $this->enquiryService->listForAdmin(auth()->user(), $perPage, $sort, $order);

        return response()->json([
            'items' => AdminBookingResource::collection($paginator->items()),
            'meta'  => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        try {
            $enquiry = $this->enquiryService->getById((int) $id);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        return response()->json((new AdminEnquiryDetailResource($enquiry))->resolve());
    }

    public function updateStatus(UpdateEnquiryStatusRequest $request, string $id): JsonResponse
    {
        try {
            $this->enquiryService->updateStatus((int) $id, auth()->user(), $request->validated());
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        return response()->json(['message' => 'Status updated successfully']);
    }

    public function initiateSurvey(InitiateSurveyRequest $request, string $id): JsonResponse
    {
        $survey = $this->surveyService->initiate(
            enquiryId:  (int) $id,
            surveyorId: $request->validated('surveyor_id'),
            actor:      auth()->user(),
        );

        return response()->json([
            'data'    => new SurveyResource($survey),
            'message' => 'Survey initiated successfully.',
        ], 201);
    }

    public function assignStaff(AssignProjectStaffRequest $request, string $id): JsonResponse
    {
        $team = $this->assignmentService->assignStaff(
            enquiryId:  (int) $id,
            sections:   $request->validated(),
            assignedBy: auth()->id(),
        );

        return response()->json([
            'data'    => $team,
            'message' => 'Staff assigned successfully.',
        ]);
    }

    public function team(string $id): JsonResponse
    {
        $team = $this->assignmentService->getTeam((int) $id);

        return response()->json([
            'data'    => $team,
            'message' => 'OK',
        ]);
    }
}
