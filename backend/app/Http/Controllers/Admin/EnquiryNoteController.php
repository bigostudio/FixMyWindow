<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateEnquiryNoteRequest;
use App\Http\Resources\EnquiryNoteResource;
use App\Services\EnquiryNoteService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EnquiryNoteController extends Controller
{
    public function __construct(
        private readonly EnquiryNoteService $service,
    ) {}

    public function store(CreateEnquiryNoteRequest $request): JsonResponse
    {
        try {
            $note = $this->service->create($request->validated(), auth()->user());
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json([
            'data'    => new EnquiryNoteResource($note),
            'message' => 'Note created successfully.',
        ], 201);
    }

    public function index(string $id): JsonResponse
    {
        try {
            $notes = $this->service->listByEnquiry((int) $id);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json([
            'data'    => EnquiryNoteResource::collection($notes),
            'message' => 'OK',
        ]);
    }
}
