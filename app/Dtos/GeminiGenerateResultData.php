<?php

namespace App\Dtos;

use App\Enums\Errors\GeminiError;
use App\Exceptions\GeminiException;
use App\ValueObjects\GeminiResponseCandidate;
use Illuminate\Support\Facades\Log;

class GeminiGenerateResultData
{
    public function __construct(
        public readonly GeminiResponseCandidate $candidate
    ) {}

    /**
     * AIが書いた生のテキストを返す（デバッグや要約などに便利）
     */
    public function getRawText(): string
    {
        return $this->candidate->content['parts'][0]['text'] ?? '';
    }

    /**
     * テキストをJSONとしてパースして取得する
     */
    public function getData(): array
    {

        $text = $this->getRawText();

        if (empty($text)) {
            throw new GeminiException(GeminiError::INTERNAL_ERROR);
        }

        $decoded = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("Gemini JSON Parse Error", [
                'raw' => $text,
                'error' => json_last_error_msg()
            ]);
            throw new GeminiException(GeminiError::INTERNAL_ERROR);
        }

        return $decoded;
    }

    /**
     * 保存用のメタデータを取得
     */
    public function getMetadata(): array
    {
        return $this->candidate->toMetadataArray();
    }

    /**
     * 指定キーの取得（基本）
     */
    public function get(string $key, mixed $default = []): mixed
    {
        try {
            $data = $this->getData();
            return $data[$key] ?? $default;
        } catch (\Exception $e) {
            return $default;
        }
    }
}
