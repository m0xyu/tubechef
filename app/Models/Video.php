<?php

namespace App\Models;

use App\Enums\RecipeGenerationStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $channel_id
 * @property string $video_id
 * @property string $title
 * @property string|null $description
 * @property string|null $thumbnail_url
 * @property \Illuminate\Support\Carbon $published_at
 * @property int|null $view_count
 * @property int|null $like_count
 * @property int|null $comment_count
 * @property array<array-key, mixed>|null $topic_categories
 * @property \Illuminate\Support\Carbon $fetched_at
 * @property string|null $category_id YouTubeカテゴリID
 * @property int|null $duration 動画の長さ（秒）
 * @property RecipeGenerationStatus $recipe_generation_status
 * @property string|null $recipe_generation_error_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Channel $channel
 * @property-read \App\Models\Recipe|null $recipe
 * @property-read mixed $url
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereChannelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereCommentCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereFetchedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereLikeCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereRecipeGenerationErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereRecipeGenerationStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereThumbnailUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereTopicCategories($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereVideoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereViewCount($value)
 * @method static \Database\Factories\VideoFactory factory($count = null, $state = [])
 * @mixin \Eloquent
 */
class Video extends Model
{
    /** @use HasFactory<\Database\Factories\VideoFactory> */
    use HasFactory;

    protected $fillable = [
        'video_id',
        'title',
        'description',
        'thumbnail_url',
        'view_count',
        'like_count',
        'comment_count',
        'topic_categories',
        'category_id',
        'channel_id',
        'duration',
        'published_at',
        'fetched_at',
        'recipe_generation_status',
        'recipe_generation_error_message',
    ];

    protected $casts = [
        'topic_categories' => 'array',
        'published_at' => 'datetime',
        'fetched_at' => 'datetime',
        'recipe_generation_status' => RecipeGenerationStatus::class,
    ];

    /**
     * 動画に関連するチャンネルを取得する
     * @return BelongsTo<Channel, $this>
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * 動画に関連するレシピを取得する
     * @return HasOne<Recipe, $this>
     */
    public function recipe(): HasOne
    {
        return $this->hasOne(Recipe::class);
    }

    /**
     * YouTubeの完全なURLを取得するアクセサ
     * 使い方: $video->url
     * 結果: https://www.youtube.com/watch?v=TOCBGexkYvw
     * 
     * @return Attribute<string, string>
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn() => 'https://www.youtube.com/watch?v=' . $this->video_id,
        );
    }

    /**
     * 動画のレシピ生成を完了状態に更新する
     * @return void
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'recipe_generation_status' => RecipeGenerationStatus::COMPLETED,
            'recipe_generation_error_message' => null,
        ]);
    }

    /**
     * リトライ回数の上限に達しているか判定
     * @return bool
     */
    public function hasExceededRetryLimit(): bool
    {
        // 失敗ステータス以外なら、そもそもリトライ上限という概念は適用外（あるいはfalse）
        // ここでは「失敗していて、かつ回数オーバー」を判定
        return $this->recipe_generation_status === RecipeGenerationStatus::FAILED
            && $this->generation_retry_count >= config('services.gemini.retry_count', 2);
    }

    /**
     * すでに生成済み、または生成処理中か判定
     * @return bool
     */
    public function isGenerationProcessingOrCompleted(): bool
    {
        return $this->recipe_generation_status !== RecipeGenerationStatus::FAILED;
    }
}
