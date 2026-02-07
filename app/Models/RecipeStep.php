<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\Recipe|null $recipe
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep query()
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
