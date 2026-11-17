<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\Services\ListCategoriesService;
use App\Infrastructure\Http\Presenters;
use Illuminate\Http\JsonResponse;

/** GET /categories: the five categories ordered by name. */
final readonly class CategoryController
{
    public function __construct(private ListCategoriesService $categories)
    {
    }

    public function index(): JsonResponse
    {
        return new JsonResponse(array_map(Presenters::category(...), $this->categories->list()), 200, [], JSON_UNESCAPED_UNICODE);
    }
}
