<?php

namespace App\Enums\Errors;

enum RecipeError: string
{
    // AIが「これはレシピ動画じゃない」と判断した
    case NOT_A_RECIPE = 'not_a_recipe';

        // Gemini APIがエラーを吐いた（通信エラー、レート制限など）
    case GENERATION_FAILED = 'generation_failed';

        // DB保存に失敗した
    case SAVE_FAILED = 'save_failed';

        // 既にレシピが存在する（再生成しようとした場合など）
    case ALREADY_EXISTS = 'already_exists';

    /**
     * エラーメッセージを取得（ログやデバッグ用）
     */
    public function message(): string
    {
        return match ($this) {
            self::NOT_A_RECIPE => 'この動画はレシピとして認識されませんでした。',
            self::GENERATION_FAILED => 'AIによるレシピ生成に失敗しました。',
            self::SAVE_FAILED => 'レシピの保存に失敗しました。',
            self::ALREADY_EXISTS => 'この動画のレシピは既に存在します。',
        };
    }
}
