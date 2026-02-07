<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\Recipe|null $recipe
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeTip newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeTip newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeTip query()
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
