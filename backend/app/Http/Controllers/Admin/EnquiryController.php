<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateEnquiryStatusRequest;
use App\Http\Resources\AdminBookingResource;
use App\Http\Resources\AdminEnquiryDetailResource;
use App\Services\EnquiryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnquiryController extends Controller
{
    public function __construct(
        private readonly EnquiryService $enquiryService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('limit', 20), 100);
        $sort    = $request->query('sort', 'created_at');
        $order   = $request->query('order', 'desc');

        $paginator = $this->enquiryService->listForAdmin($perPage, $sort, $order);

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

    public function show(int $id): JsonResponse
    {
        try {
            $enquiry = $this->enquiryService->getById($id);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        return response()->json((new AdminEnquiryDetailResource($enquiry))->resolve());
    }

    public function updateStatus(UpdateEnquiryStatusRequest $request, int $id): JsonResponse
    {
        try {
            $this->enquiryService->updateStatus($id, auth()->user(), $request->validated());
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        return response()->json(['message' => 'Status updated successfully']);
    }
}
