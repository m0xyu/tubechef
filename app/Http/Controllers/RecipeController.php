<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipeListResource;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

use function Laravel\Prompts\select;

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
            return Recipe::select([
                'id',
                'title',
                'slug',
                'cooking_time',
                'video_id',
                'dish_id',
                'created_at',
            ])->with([
                'video:id,channel_id,thumbnail_url',
                'video.channel:id,name',
                'dish:id,name',
            ])
                ->latest()
                ->paginate(20);
        });

        return RecipeListResource::collection($recipes);
    }

    /**
     * レシピ詳細を取得
     * @param string $slug
     * @return RecipeResource
     */
    public function show(string $slug): RecipeResource
    {
        $cacheKey = "recipe_show_{$slug}";

        $data = Cache::remember($cacheKey, now()->addDay(), function () use ($slug) {
            return Recipe::with([
                    'video.channel', 
                    'dish', 
                    'ingredients', 
                    'steps.tips', 
                    'tips'
                ])
                ->where('slug', $slug)
                ->firstOrFail();
        });

        return new RecipeResource($data);
    }
}
