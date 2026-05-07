<?php

namespace App\Services\LLM;

use App\Dtos\LLMRequestData;
use App\Dtos\LLMResponseData;

interface LLMServiceInterface
{
    /**
     * 動画情報からレシピを生成する
     */
    public function generate(LLMRequestData $request): LLMResponseData;
}
