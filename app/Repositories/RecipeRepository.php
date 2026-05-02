<?php

namespace App\Repositories;

use App\Models\Recipe;
use App\Repositories\Contracts\RecipeRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class RecipeRepository implements RecipeRepositoryInterface
{
    /**
     * Paginate recipes for listing.
     * 
     * @param int $page
     * @return LengthAwarePaginator
     */
    public function paginateForList(int $page): LengthAwarePaginator
    {
        $cacheKey = "recipes_index_page_{$page}";

        return Cache::tags(['recipes'])->remember($cacheKey, now()->addMinutes(10), function () use ($page) {
            return Recipe::select(['id', 'title', 'slug', 'cooking_time', 'video_id', 'dish_id', 'created_at'])
                ->with(['video.channel', 'dish'])
                ->latest()
                ->paginate(20, ['*'], 'page', $page);
        });
    }

    /**
     * Find a recipe by its slug or fail.
     * 
     * @param string $slug
     * @return Recipe
     */
    public function findBySlugOrFail(string $slug): Recipe
    {
        $cacheKey = "recipe_show_{$slug}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($slug) {
            return Recipe::with(['video.channel', 'dish', 'ingredients', 'steps.tips', 'tips'])
                ->where('slug', $slug)
                ->firstOrFail();
        });
    }
}
