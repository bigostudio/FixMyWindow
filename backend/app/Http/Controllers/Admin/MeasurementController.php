<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateMeasurementRequest;
use App\Http\Requests\Admin\UpdateMeasurementRequest;
use App\Http\Resources\MeasurementResource;
use App\Services\MeasurementService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MeasurementController extends Controller
{
    public function __construct(
        private readonly MeasurementService $service,
    ) {}

    public function store(CreateMeasurementRequest $request): JsonResponse
    {
        $measurement = $this->service->create($request->validated());

        return response()->json([
            'data'    => new MeasurementResource($measurement),
            'message' => 'Measurement created successfully.',
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        try {
            $measurement = $this->service->findById($id);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json([
            'data'    => new MeasurementResource($measurement),
            'message' => 'OK',
        ]);
    }

    public function update(UpdateMeasurementRequest $request, int $id): JsonResponse
    {
        try {
            $measurement = $this->service->update($id, $request->validated());
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json([
            'data'    => new MeasurementResource($measurement),
            'message' => 'Measurement updated successfully.',
        ]);
    }
}
