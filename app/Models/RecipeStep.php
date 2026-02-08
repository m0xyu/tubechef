<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $recipe_id
 * @property int $step_number
 * @property string $description
 * @property int|null $start_time_in_seconds 開始時間(秒)
 * @property int|null $end_time_in_seconds 終了時間(秒)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Recipe $recipe
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep whereEndTimeInSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep whereRecipeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep whereStartTimeInSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep whereStepNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RecipeStep extends Model
{
    protected $fillable = [
        'recipe_id',
        'step_number',
        'description',
        'start_time_in_seconds',
        'end_time_in_seconds',
    ];

    /**
     * @return BelongsTo<Recipe, $this>
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
