<?php

namespace App\Enums\Errors;

use App\Attributes\ErrorDetails;
use ReflectionEnumBackedCase;

enum RecipeError: string
{
    #[ErrorDetails('料理カテゴリ外のため、生成対象外です。', 422)]
    case NOT_A_RECIPE = 'not_a_recipe';

    #[ErrorDetails('AIによるレシピ生成に失敗しました。', 500)]
    case GENERATION_FAILED = 'generation_failed';

    #[ErrorDetails('レシピの保存に失敗しました。', 500)]
    case SAVE_FAILED = 'save_failed';

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
