<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function ok(array $data = [], array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'ok'   => true,
            'data' => $data,
            'meta' => (object)$meta,
        ], $status);
    }

    public static function error(string $code, string $message, array $details = [], int $status = 400, ?string $traceId = null): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'error' => [
                'code'     => $code,
                'message'  => $message,
                'details'  => (object)$details,
                'trace_id' => $traceId,
            ],
        ], $status);
    }
}
