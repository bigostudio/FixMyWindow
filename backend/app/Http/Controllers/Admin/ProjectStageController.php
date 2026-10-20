<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateProjectStageRequest;
use App\Http\Requests\Admin\UpdateProjectStageRequest;
use App\Http\Resources\ProjectStageResource;
use App\Services\ProjectStageService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProjectStageController extends Controller
{
    public function __construct(
        private readonly ProjectStageService $service,
    ) {}

    public function store(CreateProjectStageRequest $request): JsonResponse
    {
        $projectStage = $this->service->create($request->validated());

        return response()->json([
            'data'    => new ProjectStageResource($projectStage),
            'message' => 'Project stage created successfully.',
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        try {
            $projectStage = $this->service->findById($id);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json([
            'data'    => new ProjectStageResource($projectStage),
            'message' => 'OK',
        ]);
    }

    public function update(UpdateProjectStageRequest $request, int $id): JsonResponse
    {
        try {
            $projectStage = $this->service->update($id, $request->validated());
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json([
            'data'    => new ProjectStageResource($projectStage),
            'message' => 'Project stage updated successfully.',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json([
            'data'    => null,
            'message' => 'Project stage deleted successfully.',
        ]);
    }
}
