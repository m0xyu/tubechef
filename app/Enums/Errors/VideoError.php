<?php

namespace App\Enums\Errors;

use App\Attributes\ErrorDetails;
use ReflectionEnumBackedCase;

enum VideoError: string
{
    // API取得系
    #[ErrorDetails('動画情報の取得に失敗しました。URLを確認してください。', 400)]
    case FETCH_FAILED = 'fetch_failed';

    #[ErrorDetails('指定されたURLはYouTube動画ではありません。', 400)]
    case NOT_A_VIDEO = 'not_a_video';

    #[ErrorDetails('動画IDが無効です。', 422)]
    case INVALID_ID = 'invalid_id';

    // 共通系
    #[ErrorDetails('予期せぬエラーが発生しました。', 500)]
    case INTERNAL_ERROR = 'internal_error';

    public function message(): string
    {
        return $this->getDetails()->message;
    }

    public function status(): int
    {
        return $this->getDetails()->statusCode;
    }

    private function getDetails(): ErrorDetails
    {
        $reflection = new ReflectionEnumBackedCase($this, $this->name);
        $attributes = $reflection->getAttributes(ErrorDetails::class);

        return $attributes[0]->newInstance();
    }
}
