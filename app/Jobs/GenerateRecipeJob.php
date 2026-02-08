<?php

namespace App\Jobs;

use App\Actions\GenerateRecipeAction;
use App\Enums\RecipeGenerationStatus;
use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateRecipeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public $tries = 3;
    /** @var int*/
    public $maxExceptions = 3;
    /** @var int */
    public $timeout = 120;

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
        $generateRecipeAction->execute($this->video);
    }

    /**
     * Handle a job failure.
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        $this->video->update([
            'recipe_generation_status' => RecipeGenerationStatus::FAILED,
            'recipe_error_message' => $exception->getMessage(),
        ]);
    }
}
