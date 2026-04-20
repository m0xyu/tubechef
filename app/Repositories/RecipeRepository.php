<?php

namespace App\Repositories;

use App\Dtos\RecipeData;
use App\Dtos\RecipeListData;
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
     * @return LengthAwarePaginator<int, RecipeListData>
     */
    public function paginateForList(int $page): LengthAwarePaginator
    {
        $cacheKey = "recipes_index_page_{$page}";

        $cachedPaginator = Cache::tags(['recipes'])->remember($cacheKey, now()->addMinutes(10), function () use ($page) {
            return Recipe::select(['id', 'title', 'slug', 'cooking_time', 'video_id', 'dish_id', 'created_at'])
                ->with(['video.channel', 'dish'])
                ->latest()
                ->paginate(20, ['*'], 'page', $page)
                // ページネーションのアイテムをRecipeListData DTOに変換
                ->through(fn(Recipe $recipe) => RecipeListData::fromModel($recipe));
        });

        return $cachedPaginator;
    }

    /**
     * Find a recipe by its slug or fail.
     * 
     * @param string $slug
     * @return RecipeData
     */
    public function findBySlugOrFail(string $slug): RecipeData
    {
        $cacheKey = "recipe_show_{$slug}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($slug) {
            $recipe = Recipe::with(['video.channel', 'dish', 'ingredients', 'steps.tips', 'tips'])
                ->where('slug', $slug)
                ->firstOrFail();

            return RecipeData::fromModel($recipe);
        });
    }
}
