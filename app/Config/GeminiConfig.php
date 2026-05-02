<?php

namespace App\Config;

class GeminiConfig
{
    public const DEFAULT_RETRY_COUNT = 2;

    /**
     * Gemini APIのリトライ回数を取得します。
     * 環境変数が設定されていない場合はデフォルト値を使用します。
     * @return int リトライ回数
     */
    public static function retryCount(): int
    {
        $value = config('services.gemini.retry_count');
        if (is_numeric($value)) {
            return (int) $value;
        }
        return self::DEFAULT_RETRY_COUNT;
    }
}
