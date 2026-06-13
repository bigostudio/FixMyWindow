<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CreateBlueprintRequest;
use App\Http\Requests\Customer\UpdateBlueprintRequest;
use App\Http\Resources\BlueprintResource;
use App\Models\Enquiry;
use App\Services\BlueprintService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BlueprintController extends Controller
{
    public function __construct(
        private readonly BlueprintService $blueprintService,
    ) {}

    public function show(string $id): JsonResponse
    {
        $enquiry = Enquiry::find((int) $id);

        if (! $enquiry || $enquiry->customer_id !== auth('api')->id()) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

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
}
