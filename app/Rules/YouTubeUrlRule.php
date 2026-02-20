<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class YouTubeUrlRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/(?:youtube\.com|youtu\.be)/', $value)) {
            $fail('YouTubeのURLを入力してください。');
            return;
        }

        // 2. Shortsチェック
        if (str_contains($value, '/shorts/')) {
            $fail('Shorts動画は現在サポートしていません。');
        }
    }
}
