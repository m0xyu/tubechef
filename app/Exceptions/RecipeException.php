<?php

namespace App\Exceptions;

use App\Enums\Errors\RecipeError;
use Illuminate\Http\JsonResponse;

class RecipeException extends BaseException
{
    public function __construct(
        public readonly RecipeError $error,
        ?string $logMessage = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $logMessage ?? $error->message(),
            $error->message(),
            $previous
        );

        $this->withStatus($error->status());
    }

    /**
     * エラーコードを Enum の値から動的に生成
     */
    public function getErrorCode(): string
    {
        return 'recipe_' . $this->error->value;
    }

    /**
     * Enumからデフォルトのユーザーメッセージを取得
     */
    protected function getDefaultUserMessage(): string
    {
        return $this->error->message();
    }

    /**
     * Enumを取得するためのヘルパー
     */
    public function getErrorEnum(): RecipeError
    {
        return $this->error;
    }

    /**
     * Laravelがこの例外をキャッチした時に自動的にJSONレスポンスを返す
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error_code' => $this->error->value,
            'message' => $this->getDefaultUserMessage(),
        ], $this->statusCode);
    }
}
