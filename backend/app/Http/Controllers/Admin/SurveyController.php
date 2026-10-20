<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateSurveyRequest;
use App\Http\Requests\Admin\SubmitGoNoGoRequest;
use App\Http\Requests\Admin\UpdateSurveyChecklistRequest;
use App\Http\Resources\SurveyResource;
use App\Services\SurveyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    public function __construct(
        private readonly SurveyService $service,
    ) {}

    public function store(CreateSurveyRequest $request): JsonResponse
    {
        $survey = $this->service->create($request->validated(), auth()->user());

        return response()->json([
            'data'    => new SurveyResource($survey),
            'message' => 'Survey created successfully.',
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $page       = (int) $request->query('page', 1);
        $limit      = min((int) $request->query('limit', 20), 100);
        $enquiryId  = $request->query('enquiry_id') ? (int) $request->query('enquiry_id') : null;

        $paginated = $this->service->list($page, $limit, $enquiryId);

        return response()->json([
            'data' => [
                'items' => SurveyResource::collection($paginated->items()),
                'meta'  => [
                    'current_page' => $paginated->currentPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                    'last_page'    => $paginated->lastPage(),
                ],
            ],
            'message' => 'OK',
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $survey = $this->service->show($id);

        return response()->json([
            'data'    => new SurveyResource($survey),
            'message' => 'OK',
        ]);
    }

    public function updateChecklist(UpdateSurveyChecklistRequest $request, int $id): JsonResponse
    {
        $survey = $this->service->updateChecklist($id, $request->validated());

        return response()->json([
            'data'    => new SurveyResource($survey),
            'message' => 'Survey checklist updated successfully.',
        ]);
    }

    public function submitGoNoGo(SubmitGoNoGoRequest $request, int $id): JsonResponse
    {
        $survey = $this->service->submitGoNoGo(
            surveyId: $id,
            outcome:  $request->validated('outcome'),
            actor:    auth()->user(),
        );

        return response()->json([
            'data'    => new SurveyResource($survey),
            'message' => 'Go/No-Go decision recorded.',
        ]);
    }
}
