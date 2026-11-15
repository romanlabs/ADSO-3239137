<?php

namespace App\Application\Services;

use App\Application\Ports\CategoryRepository;
use App\Domain\Category;

final readonly class ListCategoriesService
{
    public function __construct(private CategoryRepository $categories)
    {
    }

    /** @return list<Category> */
    public function list(): array
    {
        return $this->categories->all();
    }
}
