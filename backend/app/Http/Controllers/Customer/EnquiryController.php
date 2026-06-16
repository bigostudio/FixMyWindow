<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\InitiateEnquiryRequest;
use App\Http\Resources\CustomerAddressResource;
use App\Http\Resources\EnquiryResource;
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
        $customerId = auth('api')->id();
        $perPage    = min((int) $request->query('limit', 20), 100);
        $sort       = $request->query('sort', 'created_at');
        $order      = $request->query('order', 'desc');

        $paginator = $this->enquiryService->listForCustomer($customerId, $perPage, $sort, $order);

        return response()->json([
            'items' => EnquiryResource::collection($paginator->items()),
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
            $enquiry = $this->enquiryService->getForCustomer($id, auth('api')->id());
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        return response()->json((new EnquiryResource($enquiry))->resolve());
    }

    public function addresses(): JsonResponse
    {
        $addresses = $this->enquiryService->getAddressesForCustomer(auth('api')->id());

        return response()->json(CustomerAddressResource::collection($addresses));
    }

    public function create(InitiateEnquiryRequest $request): JsonResponse
    {
        $enquiry = $this->enquiryService->create(
            customer: auth('api')->user(),
            data:     $request->validated(),
        );

        return response()->json((new EnquiryResource($enquiry))->resolve(), 201);
    }
}
