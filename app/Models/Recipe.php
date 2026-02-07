<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read \App\Models\Dish|null $dish
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RecipeIngredient> $ingredients
 * @property-read int|null $ingredients_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RecipeStep> $steps
 * @property-read int|null $steps_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RecipeTip> $tips
 * @property-read int|null $tips_count
 * @property-read \App\Models\Video|null $video
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe query()
 * @mixin \Eloquent
 */
class Recipe extends Model
{
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
