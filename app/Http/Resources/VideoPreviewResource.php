<?php

namespace App\Http\Resources;

use App\Enums\RecipeGenerationStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Video
 * @property string $video_id
 * @property string $title
 * @property string $video_url
 * @property string $description
 * @property string $thumbnail_url
 * @property int|null $duration
 * @property string $published_at
 * @property string $channel_name
 * @property string $channel_id
 * @property \App\Models\Channel|null $channel
 * @property \App\Enums\RecipeGenerationStatus|null $recipe_generation_status
 * @property string|null $recipe_generation_error_message
 * @property int $generation_retry_count
 * @property-read \App\Models\Recipe|null $recipe
 * @property bool $is_registered
 * @property bool $is_retryable
 * 
 */
class VideoPreviewResource extends JsonResource
{
    /**
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $channelName = $this->channel ? $this->channel->name : ($this->channel_name ?? '');
        $channelId = $this->channel ? $this->channel->channel_id : ($this->channel_id ?? '');


        return [
            'video_id' => $this->video_id,
            'video_url' => $this->url,
            'title' => $this->title,
            'description' => $this->description,
            'thumbnail_url' => $this->thumbnail_url,
            'duration' => $this->duration,
            'published_at' => $this->published_at,
            'channel' => [
                'name' => $channelName,
                'id' => $channelId,
            ],
            'action_type' => $this->determineActionType(),
            'stats' => [
                'is_registered' => $this->exists,
                'is_retryable' => $this->is_retryable,
                'retry_count' => $this->generation_retry_count ?? 0,
            ],
            'recipe_generation_status' => $this->recipe_generation_status ?? null,
            'recipe_generation_error_message' => $this->recipe_generation_error_message,
            'recipe_slug' => $this->resource->relationLoaded('recipe') && $this->recipe
                ? $this->recipe->slug
                : null,
        ];
    }

    /**
     * フロントエンドが表示すべきボタンの種類を判定する
     * @return string view_recipe,generate,limit_exceededのいずれか
     */
    private function determineActionType(): string
    {
        // レシピが既に存在する -> 「レシピを見る」
        if ($this->resource->relationLoaded('recipe') && $this->recipe) {
            return 'view_recipe';
        }

        // 2. 生成中 -> 「処理中（スピナー表示）」 ★これを追加推奨
        $status = $this->recipe_generation_status;
        if ($status === RecipeGenerationStatus::PROCESSING || $status === RecipeGenerationStatus::PENDING) {
            return 'processing';
        }

        // DBに存在し、かつリトライ上限を超えている -> 「制限到達（生成不可）」
        // ※ hasExceededRetryLimit は「失敗かつ回数オーバー」の時に true を返す
        if ($this->exists && $this->hasExceededRetryLimit()) {
            return 'limit_exceeded';
        }

        // 上記以外（新規、またはリトライ回数が残っている） -> 「生成する」
        return 'generate';
    }
}
