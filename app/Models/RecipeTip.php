<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $recipe_id
 * @property int|null $recipe_step_id
 * @property string $description コツの内容
 * @property int|null $start_time_in_seconds
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Recipe $recipe
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeTip newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeTip newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeTip query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeTip whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeTip whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeTip whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeTip whereRecipeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeTip whereRecipeStepId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeTip whereStartTimeInSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeTip whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RecipeTip extends Model
{
    protected $fillable = [
        'recipe_id',
        'recipe_step_id',
        'description',
        'start_time_in_seconds',

    ];

    /**
     * @return BelongsTo<Recipe, $this>
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
