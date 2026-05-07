<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipeListResource;
use App\Http\Resources\RecipeResource;
use App\Repositories\Contracts\RecipeRepositoryInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    public function __construct(
        private readonly RecipeRepositoryInterface $recipeRepository
    ) {}

    /**
     * レシピ一覧を取得
     * @param Request $request
     * @return AnonymousResourceCollection<RecipeListResource>
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $page = $request->integer('page', 1);

        $recipes = $this->recipeRepository->paginateForList($page);

        return RecipeListResource::collection($recipes);
    }

    /**
     * レシピ詳細を取得
     * @param string $slug
     * @return RecipeResource
     */
    public function show(string $slug): RecipeResource
    {
        $data = $this->recipeRepository->findBySlugOrFail($slug);

        return new RecipeResource($data);
    }
}
