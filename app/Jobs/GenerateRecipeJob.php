<?php

namespace App\Jobs;

use App\Actions\GenerateRecipeAction;
use App\Enums\Errors\RecipeError;
use App\Enums\RecipeGenerationStatus;
use App\Exceptions\RecipeException;
use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateRecipeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public $tries = 3;
    /** @var int*/
    public $maxExceptions = 3;
    /** @var int */
    public $timeout = 160;
    /** @var int */
    public int $backoff = 10;

    /**
     * @var Video $video
     */
    protected $video;

    /**
     * Create a new job instance.
     * @param Video $video
     */
    public function __construct(Video $video)
    {
        $this->video = $video;
    }

    /**
     * Execute the job.
     * @param GenerateRecipeAction $generateRecipeAction
     * @return void
     */
    public function handle(GenerateRecipeAction $generateRecipeAction): void
    {
        Log::info("Job開始: VideoID {$this->video->video_id}");

        try {
            if ($this->video->recipe_generation_status !== RecipeGenerationStatus::PROCESSING) {
                $this->video->update(['recipe_generation_status' => RecipeGenerationStatus::PROCESSING]);
            }

            $generateRecipeAction->execute($this->video);

            $this->video->markAsCompleted();

            Log::info("Job完了: VideoID {$this->video->video_id}");
        } catch (RecipeException $e) {
            Log::warning("生成失敗(リトライなし): {$e->getMessage()}");
            $this->fail($e);
        } catch (Throwable $e) {
            Log::error("システムエラー(リトライ対象): {$e->getMessage()}");
            throw $e; // Laravelがこれを検知してリトライ処理に回す
        }
    }

    /**
     * Handle a job failure.
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        $retryLimit = config('services.gemini.retry_count', 2);

        $isFatal = ($exception instanceof RecipeException && $exception->error === RecipeError::NOT_A_RECIPE);
        $newCount = $isFatal ? $retryLimit + 1 : $this->video->generation_retry_count + 1;

        $this->video->update([
            'recipe_generation_status' => RecipeGenerationStatus::FAILED,
            'recipe_generation_error_message' => $exception->getMessage(),
            'generation_retry_count' => $newCount,
        ]);
    }
}
