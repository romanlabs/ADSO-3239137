<?php

namespace App\Infrastructure\Persistence;

use App\Application\Ports\CategoryRepository;
use App\Domain\Category;
use Illuminate\Database\ConnectionInterface;

/** Q4. The five categories are seeded by the migration and never change through the API. */
final readonly class DbCategoryRepository implements CategoryRepository
{
    public function __construct(private ConnectionInterface $connection)
    {
    }

    public function all(): array
    {
        return $this->connection->table('laptops.category')
            ->select(['id', 'name'])
            ->orderByRaw('name COLLATE "C"')
            ->get()
            ->map(fn (object $row) => new Category($row->id, $row->name))
            ->values()
            ->all();
    }

    public function find(string $id): ?Category
    {
        if (! SqlValues::isUuid($id)) {
            return null;
        }

        $row = $this->connection->table('laptops.category')->where('id', $id)->first(['id', 'name']);

        return $row === null ? null : new Category($row->id, $row->name);
    }
}
