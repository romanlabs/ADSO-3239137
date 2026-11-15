<?php

namespace Tests\Fakes;

use App\Application\Ports\CategoryRepository;
use App\Domain\Category;

/** Same five categories and fixed ids as the migration seed. */
final class InMemoryCategoryRepository implements CategoryRepository
{
    public const BASIC = '11111111-1111-4111-8111-111111111111';

    public const DESIGN = '22222222-2222-4222-8222-222222222222';

    public const PROGRAMMING = '33333333-3333-4333-8333-333333333333';

    public const TABLETS = '44444444-4444-4444-8444-444444444444';

    public const ACCESSORIES = '55555555-5555-4555-8555-555555555555';

    /** @var array<string, Category> */
    private array $categories;

    public function __construct()
    {
        $this->categories = [
            self::BASIC => new Category(self::BASIC, 'Básicos'),
            self::DESIGN => new Category(self::DESIGN, 'Diseño'),
            self::PROGRAMMING => new Category(self::PROGRAMMING, 'Programación'),
            self::TABLETS => new Category(self::TABLETS, 'Tabletas'),
            self::ACCESSORIES => new Category(self::ACCESSORIES, 'Accesorios'),
        ];
    }

    public function all(): array
    {
        $all = array_values($this->categories);
        usort($all, fn (Category $a, Category $b) => strcmp($a->name, $b->name));

        return $all;
    }

    public function find(string $id): ?Category
    {
        return $this->categories[$id] ?? null;
    }

    /** Lets a test rename a category the way an operator would directly in the catalog. */
    public function rename(string $id, string $name): Category
    {
        return $this->categories[$id] = new Category($id, $name);
    }
}
