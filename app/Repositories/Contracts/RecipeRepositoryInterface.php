<?php

namespace App\Repositories\Contracts;

use App\Models\Recipe;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RecipeRepositoryInterface
{
    /**
     * @param int $page
     * @return LengthAwarePaginator
     */
    public function paginateForList(int $page): LengthAwarePaginator;

    /**
     * @param string $slug
     * @return Recipe
     */
    public function findBySlugOrFail(string $slug): Recipe;
}
