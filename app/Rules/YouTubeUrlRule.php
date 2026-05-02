<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class YouTubeUrlRule implements ValidationRule
{
    /**
     * YouTubeのURLかつShortsでないことを検証する
     *
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('YouTubeのURLを正しく入力してください。');
            return;
        }

        $pattern = '/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+$/';
        if (!preg_match($pattern, $value)) {
            $fail('YouTubeのURLを入力してください。');
            return;
        }

        // 2. Shortsチェック
        $needles = '/shorts/';
        if (str_contains($value, $needles)) {
            $fail('Shorts動画は現在サポートしていません。');
        }
    }
}
