<?php

namespace App\Repositories\Contracts;

use App\Dtos\RecipeData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RecipeRepositoryInterface
{
    public function paginateForList(int $page): LengthAwarePaginator;
    public function findBySlugOrFail(string $slug): RecipeData;
}
