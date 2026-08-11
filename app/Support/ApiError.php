<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Formato JSON padronizado para rotas api/*.
 *
 * Shape:
 *   { "message": string, "code"?: string, "errors"?: object }
 */
final class ApiError
{
    /**
     * @param  array<string, mixed>|null  $errors
     */
    public static function make(
        string $message,
        int $status = 400,
        ?string $code = null,
        ?array $errors = null,
    ): JsonResponse {
        $payload = ['message' => $message];

        if ($code !== null) {
            $payload['code'] = $code;
        }

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
