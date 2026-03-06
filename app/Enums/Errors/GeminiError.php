<?php

namespace App\Enums\Errors;

use App\Attributes\ErrorDetails;
use ReflectionEnumBackedCase;

enum GeminiError: string
{
    #[ErrorDetails('リクエストの形式が正しくありません。', 400)]
    case INVALID_ARGUMENT = 'invalid_argument';

    #[ErrorDetails('課金設定またはリージョンの制限により利用できません。', 400)]
    case FAILED_PRECONDITION = 'failed_precondition';

    #[ErrorDetails('APIキーの権限が不足しています。', 403)]
    case PERMISSION_DENIED = 'permission_denied';

    #[ErrorDetails('指定されたリソースが見つかりませんでした。', 404)]
    case NOT_FOUND = 'not_found';

    #[ErrorDetails('レート制限を超過しました。しばらく待ってから再試行してください。', 429)]
    case RESOURCE_EXHAUSTED = 'resource_exhausted';

    #[ErrorDetails('予期せぬエラーが発生しました。', 500)]
    case INTERNAL_ERROR = 'internal_error';

    #[ErrorDetails('サービスが一時的に過負荷、またはダウンしています。', 503)]
    case UNAVAILABLE = 'unavailable';

    #[ErrorDetails('処理がタイムアウトしました。', 504)]
    case DEADLINE_EXCEEDED = 'deadline_exceeded';

    // 共通のメソッド（VideoErrorと同様）
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
