<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResponseEnvelope
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response instanceof JsonResponse) {
            return $response;
        }

        $data = $response->getData(true);

        // Already wrapped by the exception handler — don't double-wrap.
        if (is_array($data) && array_key_exists('success', $data)) {
            return $response;
        }

        $status = $response->getStatusCode();

        return new JsonResponse([
            'success' => $status < 400,
            'data'    => $data,
            'message' => 'OK',
        ], $status);
    }
}
