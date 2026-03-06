<?php

namespace App\Actions;

use App\Dtos\GeneratedRecipeData;
use App\Enums\Errors\RecipeError;
use App\Exceptions\RecipeException;
use App\Models\Recipe;
use App\Models\Video;
use App\Services\LLM\LLMServiceFactory;
use App\Services\LLM\LLMServiceInterface;
use App\Services\RecipeService;
use App\Services\Schemas\RecipeSchema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GenerateRecipeAction
{
    protected LLMServiceInterface $llmService;

    public function __construct(
        LLMServiceFactory $factory,
        protected RecipeService $recipeService,
        protected VideoMetadataUpdateAction $videoMetadataUpdateAction

    ) {
        $this->llmService = $factory->make();
    }

    /**
     * 動画のタイトルと説明文からレシピを生成し、保存する
     * @param Video $video
     * @return Recipe
     * @throws \RuntimeException
     */
    public function execute(Video $video): Recipe
    {
        if ($video->recipe()->exists()) {
            return $video->recipe;
        }

        $prompt = $this->buildPrompt($video->title, $video->description);
        $systemInstruction = $this->getInstruction();

        try {
            $result = $this->llmService->generateStructured($prompt, RecipeSchema::get(), $systemInstruction, $video->url);
            Log::info("Gemini生成成功: VideoID {$video->id}");
        } catch (Throwable $e) {
            Log::error("Gemini生成エラー: VideoID {$video->id}", ['error' => $e->getMessage()]);
            throw new RecipeException(RecipeError::GENERATION_FAILED, previous: $e);
        }

        $recipeData = GeneratedRecipeData::fromArray($result->getData());
        $metadata = $result->getMetadata();

        if (!$recipeData->isRecipe) {
            $this->videoMetadataUpdateAction->execute($video, $metadata);
            throw new RecipeException(RecipeError::NOT_A_RECIPE);
        }

        try {
            return DB::transaction(function () use ($video, $recipeData, $metadata) {
                $recipe = $this->recipeService->storeGeneratedRecipe($video, $recipeData);

                $this->videoMetadataUpdateAction->execute($video, $metadata);
                $video->markAsCompleted();

                Cache::tags(['recipes'])->flush();
                return $recipe;
            });
        } catch (Throwable $e) {
            throw new RecipeException(RecipeError::SAVE_FAILED, previous: $e);
        }
    }

    /** @return string */
    private function getInstruction(): string
    {
        return 'あなたはプロの料理研究家兼データエンジニアです。';
    }

    /**
     * @param string $title
     * @param string $description
     * @return string
     */
    private function buildPrompt($title, $description): string
    {
        return <<<EOT
            提供される「YouTube動画（映像・音声）」および「タイトル・概要欄」を総合的に分析し、正確なレシピデータを抽出してください。
            概要欄に分量や手順が記載されていない場合は、動画内の映像や音声解説から情報を補完してください。
            料理動画ではない場合（ゲーム実況やニュースなど）は、is_recipeをfalseにしてください。
    
            ## 動画タイトル
            {$title}

            ## 概要欄
            {$description}
        EOT;
    }
}
