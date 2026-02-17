<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipeListResource;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    /**
     * レシピ一覧を取得
     * @param Request $request
     * @return AnonymousResourceCollection<RecipeListResource>
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $page = $request->get('page', 1);
        $cacheKey = "recipes_index_page_{$page}";

        $recipes = Cache::tags(['recipes'])->remember($cacheKey, now()->addMinutes(10), function () {
            return Recipe::with(['video.channel', 'dish'])
                ->latest()
                ->paginate(20);
        });

        return RecipeListResource::collection($recipes);
    }

    /**
     * レシピ詳細を取得
     * @param Recipe $recipe
     * @return RecipeResource
     */
    public function show(Recipe $recipe): RecipeResource
    {
        $cacheKey = "recipe_show_{$recipe->slug}";

        $data = Cache::remember($cacheKey, now()->addDay(), function () use ($recipe) {
            return $recipe->load(['video.channel', 'dish', 'ingredients', 'steps', 'tips']);
        });

        return new RecipeResource($data);
    }
}
