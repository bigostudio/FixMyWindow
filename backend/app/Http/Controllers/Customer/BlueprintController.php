<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\GenerateBlueprintRequest;
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

    public function index(): JsonResponse
    {
        $blueprints = $this->blueprintService->getByCustomerId(auth('api')->id());

        return response()->json([
            'data'    => BlueprintResource::collection($blueprints),
            'message' => 'OK',
        ]);
    }

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

    public function generate(GenerateBlueprintRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $blueprint = $this->blueprintService->generateForDraft(
            customerId: auth('api')->id(),
            data:       $validated,
        );

        return response()->json([
            'data'    => new BlueprintResource($blueprint),
            'message' => 'Blueprint created successfully.',
        ], 201);
    }

    public function updateDraft(UpdateBlueprintRequest $request, string $id): JsonResponse
    {
        $blueprint = $this->blueprintService->updateForDraft(
            enquiryId:  (int) $id,
            customerId: auth('api')->id(),
            data:       $request->validated(),
        );

        return response()->json([
            'data'    => new BlueprintResource($blueprint),
            'message' => 'Blueprint updated successfully.',
        ]);
    }

}
