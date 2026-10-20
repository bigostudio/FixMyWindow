<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\GenerateBlueprintRequest;
use App\Http\Requests\Customer\UpdateBlueprintRequest;
use App\Http\Requests\Customer\UploadBlueprintPhotoRequest;
use App\Http\Resources\BlueprintPhotoResource;
use App\Http\Resources\BlueprintResource;
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

    public function store(GenerateBlueprintRequest $request): JsonResponse
    {
        $blueprint = $this->blueprintService->createForCustomer(
            customerId: auth('api')->id(),
            data:       $request->validated(),
        );

        return response()->json([
            'data'    => new BlueprintResource($blueprint),
            'message' => 'Blueprint created successfully.',
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        try {
            $blueprint = $this->blueprintService->getByIdForCustomer((int) $id, auth('api')->id());
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json([
            'data'    => new BlueprintResource($blueprint),
            'message' => 'OK',
        ]);
    }

    public function update(UpdateBlueprintRequest $request, string $id): JsonResponse
    {
        try {
            $blueprint = $this->blueprintService->updateForCustomer(
                blueprintId: (int) $id,
                customerId:  auth('api')->id(),
                data:        $request->validated(),
            );
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json([
            'data'    => new BlueprintResource($blueprint),
            'message' => 'Blueprint updated successfully.',
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->blueprintService->deleteForCustomer((int) $id, auth('api')->id());
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(['message' => 'Blueprint deleted successfully.']);
    }

    public function storePhoto(UploadBlueprintPhotoRequest $request, string $enquiryId): JsonResponse
    {
        $photos = $this->blueprintService->uploadPhotos(
            enquiryId:       (int) $enquiryId,
            files:           $request->file('photos'),
            uploaderType:    'customer',
            uploaderId:      auth('api')->id(),
            customerOwnerId: auth('api')->id(),
        );

        return response()->json([
            'data'    => BlueprintPhotoResource::collection($photos),
            'message' => 'Photos uploaded successfully.',
        ], 201);
    }

    public function indexPhoto(string $enquiryId): JsonResponse
    {
        try {
            $photos = $this->blueprintService->getPhotos((int) $enquiryId, auth('api')->id());
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json([
            'data'    => BlueprintPhotoResource::collection($photos),
            'message' => 'OK',
        ]);
    }

    public function destroyPhoto(string $enquiryId, string $photoId): JsonResponse
    {
        try {
            $this->blueprintService->deletePhoto((int) $enquiryId, (int) $photoId, auth('api')->id());
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(['message' => 'Photo deleted successfully.']);
    }
}
