<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookEnquiryRequest;
use App\Http\Resources\EnquiryResource;
use App\Services\EnquiryService;
use Illuminate\Http\JsonResponse;

class EnquiryController extends Controller
{
    public function __construct(
        private readonly EnquiryService $enquiryService,
    ) {}

    public function book(BookEnquiryRequest $request): JsonResponse
    {
        $customer = auth('api')->user();

        $enquiry = $this->enquiryService->book(
            customerId: $customer->id,
            actorName:  $customer->name ?? $request->input('billing.name'),
            data:       $request->validated(),
        );

        return response()->json((new EnquiryResource($enquiry))->resolve(), 201);
    }
}
