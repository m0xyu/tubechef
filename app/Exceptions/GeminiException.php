<?php

namespace App\Exceptions;

use App\Enums\Errors\GeminiError;
use Illuminate\Http\JsonResponse;
use Throwable;

class GeminiException extends BaseException
{
    public function __construct(
        protected readonly GeminiError $error,
        ?string $logMessage = null,
        ?Throwable $previous = null
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
     * 例: 'gemini_invalid_argument'
     */
    public function getErrorCode(): string
    {
        return 'gemini_' . $this->error->value;
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
    public function getErrorEnum(): GeminiError
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
