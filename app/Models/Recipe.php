<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $video_id
 * @property int|null $dish_id
 * @property string $slug URL用スラッグ
 * @property string $title レシピのタイトル
 * @property string|null $summary レシピの概要・紹介文
 * @property string|null $serving_size 分量（例: 2人前）
 * @property string|null $cooking_time 調理時間（例: 15分）
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Dish|null $dish
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RecipeIngredient> $ingredients
 * @property-read int|null $ingredients_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RecipeStep> $steps
 * @property-read int|null $steps_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RecipeTip> $tips
 * @property-read int|null $tips_count
 * @property-read \App\Models\Video $video
 * @method static \Database\Factories\RecipeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereCookingTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereDishId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereServingSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereVideoId($value)
 * @mixin \Eloquent
 */
class Recipe extends Model
{
    /** @use HasFactory<\Database\Factories\RecipeFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'summary',
        'cooking_time',
        'serving_size',
        'video_id',
        'dish_id',
        'slug',
    ];

    /**
     * 動画情報とのリレーション
     *
     * @return BelongsTo<Video, $this>
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * 料理情報とのリレーション
     *
     * @return BelongsTo<Dish, $this>
     */
    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }

    /**
     * 材料情報とのリレーション
     *
     * @return HasMany<RecipeIngredient, $this>
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class)->orderBy('order');
    }

    /**
     * 手順情報とのリレーション
     *
     * @return HasMany<RecipeStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(RecipeStep::class)->orderBy('step_number');
    }

    /**
     * コツ・ポイント情報とのリレーション
     *
     * @return HasMany<RecipeTip, $this>
     */
    public function tips(): HasMany
    {
        return $this->hasMany(RecipeTip::class);
    }
}
