<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Models\ContactSubmission;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function store(StoreContactRequest $request): JsonResponse
    {
        ContactSubmission::create($request->validated());

        return response()->json(['message' => 'Your message has been received. We will get back to you shortly.'], 201);
    }
}
