<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateBlueprintRequest;
use App\Http\Requests\Admin\UpdateBlueprintRequest;
use App\Http\Requests\Admin\UploadBlueprintPhotoRequest;
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

    public function store(CreateBlueprintRequest $request, string $id): JsonResponse
    {
        $blueprint = $this->blueprintService->createForAdmin((int) $id, $request->validated());

        return response()->json([
            'data'    => new BlueprintResource($blueprint),
            'message' => 'Blueprint created successfully.',
        ], 201);
    }

    public function update(UpdateBlueprintRequest $request, string $id): JsonResponse
    {
        try {
            $blueprint = $this->blueprintService->updateById((int) $id, $request->validated());
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

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->blueprintService->deleteById((int) $id);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(['message' => 'Blueprint deleted successfully.']);
    }

    public function storePhoto(UploadBlueprintPhotoRequest $request, string $enquiryId): JsonResponse
    {
        try {
            $photos = $this->blueprintService->uploadPhotos(
                enquiryId:    (int) $enquiryId,
                files:        $request->file('photos'),
                uploaderType: 'admin',
                uploaderId:   auth('admin')->id(),
            );
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json([
            'data'    => BlueprintPhotoResource::collection($photos),
            'message' => 'Photos uploaded successfully.',
        ], 201);
    }

    public function indexPhoto(string $enquiryId): JsonResponse
    {
        try {
            $photos = $this->blueprintService->getPhotos((int) $enquiryId);
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
            $this->blueprintService->deletePhoto((int) $enquiryId, (int) $photoId);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(['message' => 'Photo deleted successfully.']);
    }
}
