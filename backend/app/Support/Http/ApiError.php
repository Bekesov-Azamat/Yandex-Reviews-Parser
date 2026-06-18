<?php

namespace App\Support\Http;

use Illuminate\Http\JsonResponse;

class ApiError
{
    public static function response(
        string $code,
        string $message,
        int $status,
        array $meta = [],
    ): JsonResponse {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'meta' => $meta,
            ],
        ], $status);
    }
}
