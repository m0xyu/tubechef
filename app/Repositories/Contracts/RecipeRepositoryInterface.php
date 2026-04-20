<?php

namespace App\Repositories\Contracts;

use App\Dtos\RecipeData;
use App\Dtos\RecipeListData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RecipeRepositoryInterface
{
    /**
     * @param int $page
     * @return LengthAwarePaginator<int, RecipeListData>
     */
    public function paginateForList(int $page): LengthAwarePaginator;

    /**
     * @param string $slug
     * @return RecipeData
     */
    public function findBySlugOrFail(string $slug): RecipeData;
}
