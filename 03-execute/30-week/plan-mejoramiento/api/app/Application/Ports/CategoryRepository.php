<?php

namespace App\Application\Ports;

use App\Domain\Category;

interface CategoryRepository
{
    /** The five fixed categories ordered by name (Q4). @return list<Category> */
    public function all(): array;

    public function find(string $id): ?Category;
}
