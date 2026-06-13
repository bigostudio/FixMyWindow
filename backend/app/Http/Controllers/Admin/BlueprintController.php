<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateBlueprintRequest;
use App\Http\Requests\Admin\UpdateBlueprintRequest;
use App\Http\Resources\BlueprintResource;
use App\Services\BlueprintService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BlueprintController extends Controller
{
    public function __construct(
        private readonly BlueprintService $blueprintService,
    ) {}

    public function store(CreateBlueprintRequest $request, string $id): JsonResponse
    {
        try {
            $blueprint = $this->blueprintService->create((int) $id, $request->validated());
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json([
            'data'    => new BlueprintResource($blueprint),
            'message' => 'Blueprint created successfully.',
        ], 201);
    }

    public function update(UpdateBlueprintRequest $request, string $id): JsonResponse
    {
        try {
            $blueprint = $this->blueprintService->update((int) $id, $request->validated());
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json([
            'data'    => new BlueprintResource($blueprint),
            'message' => 'Blueprint updated successfully.',
        ]);
    }

    public function showByEnquiry(string $id): JsonResponse
    {
        try {
            $blueprint = $this->blueprintService->getByEnquiryId((int) $id);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json([
            'data'    => new BlueprintResource($blueprint),
            'message' => 'OK',
        ]);
    }

    public function byCustomer(string $id): JsonResponse
    {
        $blueprints = $this->blueprintService->getByCustomerId((int) $id);

        return response()->json([
            'data'    => BlueprintResource::collection($blueprints),
            'message' => 'OK',
        ]);
    }
}
