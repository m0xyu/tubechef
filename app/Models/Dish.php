<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Recipe> $recipes
 * @property-read int|null $recipes_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dish newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dish newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dish query()
 * @mixin \Eloquent
 */
class Dish extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<Recipe, $this>
     */
    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }
}
