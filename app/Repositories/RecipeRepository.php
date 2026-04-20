<?php

namespace App\Repositories;

use App\Dtos\RecipeData;
use App\Dtos\RecipeListData;
use App\Models\Recipe;
use App\Repositories\Contracts\RecipeRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class RecipeRepository implements RecipeRepositoryInterface
{
    public function paginateForList(int $page): LengthAwarePaginator
    {
        $paginator = Recipe::select([
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
            ->paginate(20, ['*'], 'page', $page);

        return $paginator->setCollection(
            $paginator->getCollection()->map(fn(Recipe $recipe) => RecipeListData::fromModel($recipe))
        );
    }

    public function findBySlugOrFail(string $slug): RecipeData
    {
        $recipe =  Recipe::with([
            'video.channel',
            'dish',
            'ingredients',
            'steps.tips',
            'tips'
        ])
            ->where('slug', $slug)
            ->firstOrFail();

        return RecipeData::fromModel($recipe);
    }
}
