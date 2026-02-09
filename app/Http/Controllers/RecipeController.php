<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipeListResource;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecipeController extends Controller
{
    /**
     * @return AnonymousResourceCollection<RecipeListResource>
     */
    public function index(): AnonymousResourceCollection
    {
        $recipes = Recipe::with(['video.channel', 'dish'])
            ->latest()
            ->paginate(20);

        return RecipeListResource::collection($recipes);
    }

    /**
     * @param Recipe $recipe
     * @return RecipeResource
     */
    public function show(Recipe $recipe): RecipeResource
    {
        $recipe->load(['video.channel', 'ingredients', 'steps', 'dish', 'tips']);
        return new RecipeResource($recipe);
    }
}
