<?php

namespace App\Exceptions;

use App\Enums\Errors\RecipeError;
use Exception;
use Illuminate\Http\JsonResponse;

class RecipeException extends Exception
{
    public function __construct(
        public readonly RecipeError $error,
        string $message = '',
        public readonly int $statusCode = 400,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message ?: $error->message(), 0, $previous);
    }

    /**
     * Laravelがこの例外をキャッチした時に自動的にJSONレスポンスを返す
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error_code' => $this->error->value,
            'message' => $this->getMessage(),
        ], $this->statusCode);
    }
}
