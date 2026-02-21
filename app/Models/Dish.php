<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Recipe> $recipes
 * @property-read int|null $recipes_count
 * @method static \Database\Factories\DishFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dish newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dish newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dish query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dish whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dish whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dish whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dish whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dish whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dish whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dish whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Dish extends Model
{
    /** @use HasFactory<\Database\Factories\DishFactory> */
    use HasFactory;

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
