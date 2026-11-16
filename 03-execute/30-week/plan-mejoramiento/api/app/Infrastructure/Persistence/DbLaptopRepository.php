<?php

namespace App\Infrastructure\Persistence;

use App\Application\Ports\LaptopRepository;
use App\Application\Ports\Page;
use App\Application\Ports\PageRequest;
use App\Domain\Category;
use App\Domain\Exceptions\LaptopNotAvailable;
use App\Domain\Exceptions\ModelCodeTaken;
use App\Domain\Laptop;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use LogicException;

/**
 * Laptops in PostgreSQL. The available units read for each laptop are remembered and used as the witness of the
 * compare-and-set of ADR-002, so one instance must live for exactly one request (bound as scoped).
 */
final class DbLaptopRepository implements LaptopRepository
{
    /** @var array<string, int> available units as last read or written, keyed by id */
    private array $readUnits = [];

    public function __construct(private readonly ConnectionInterface $connection)
    {
    }

    public function find(string $id): ?Laptop
    {
        $laptop = $this->findIncludingRetired($id);

        return $laptop === null || $laptop->isRetired() ? null : $laptop;
    }

    public function findIncludingRetired(string $id): ?Laptop
    {
        if (! SqlValues::isUuid($id)) {
            return null;
        }

        $row = $this->select()->where('l.id', $id)->first();

        return $row === null ? null : $this->remember($row);
    }

    public function findMany(array $ids): array
    {
        $ids = array_values(array_filter($ids, SqlValues::isUuid(...)));
        if ($ids === []) {
            return [];
        }

        $found = [];
        foreach ($this->select()->whereIn('l.id', $ids)->whereNull('l.deleted_at')->get() as $row) {
            $found[$row->id] = $this->remember($row);
        }

        return $found;
    }

    public function search(?string $text, ?string $categoryId, PageRequest $page): Page
    {
        if ($categoryId !== null && ! SqlValues::isUuid($categoryId)) {
            return new Page([], $page->page, $page->size, 0);
        }

        $query = $this->select()->whereNull('l.deleted_at');

        if ($categoryId !== null) {
            $query->where('l.category_id', $categoryId);
        }

        if ($text !== null) {
            $pattern = SqlValues::containsPattern($text);
            $query->where(fn (Builder $match) => $match->where('l.name', 'ilike', $pattern)->orWhere('l.brand', 'ilike', $pattern));
        }

        $total = (clone $query)->count();
        $rows = $query->orderByRaw('lower(l.name) COLLATE "C"')->orderBy('l.id')
            ->offset($page->offset())->limit($page->size)->get();

        return new Page($rows->map(fn (object $row) => $this->remember($row))->all(), $page->page, $page->size, $total);
    }

    public function modelCodeInUse(string $modelCode): bool
    {
        return $this->connection->table('laptops.laptop')
            ->where('model_code', $modelCode)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function add(Laptop $laptop): void
    {
        try {
            // A savepoint keeps the caller's transaction usable after a unique violation.
            $this->connection->transaction(fn () => $this->connection->table('laptops.laptop')->insert([
                'id' => $laptop->id,
                ...$this->columns($laptop),
                'model_code' => $laptop->modelCode->value,
            ]));
        } catch (QueryException $error) {
            if (SqlValues::isUniqueViolation($error)) {
                throw ModelCodeTaken::create($laptop->modelCode->value);
            }
            throw $error;
        }

        $this->readUnits[$laptop->id] = $laptop->availableUnits();
    }

    public function save(Laptop $laptop): void
    {
        $updated = $this->connection->table('laptops.laptop')
            ->where('id', $laptop->id)
            ->where('available_units', $this->witness($laptop->id))
            ->update($this->columns($laptop));

        if ($updated === 0) {
            throw LaptopNotAvailable::create($laptop->name(), 'total_units');
        }

        $this->readUnits[$laptop->id] = $laptop->availableUnits();
    }

    /**
     * Writes the units withdrawn by a loan, each only if it still holds the value that was read (ADR-002).
     * Runs inside the caller's transaction, so a lost race on any laptop rolls back the whole loan.
     *
     * @param  list<Laptop>  $laptops
     */
    public function storeWithdrawnUnits(array $laptops): void
    {
        foreach (array_values($laptops) as $index => $laptop) {
            $updated = $this->connection->table('laptops.laptop')
                ->where('id', $laptop->id)
                ->where('available_units', $this->witness($laptop->id))
                ->update(['available_units' => $laptop->availableUnits()]);

            if ($updated === 0) {
                throw LaptopNotAvailable::create($laptop->name(), "items[{$index}].laptop_id");
            }
        }

        foreach ($laptops as $laptop) {
            $this->readUnits[$laptop->id] = $laptop->availableUnits();
        }
    }

    /**
     * Atomic increment for a return: it does not compare with the value read, so a return never fails because another
     * loan of the same laptop crossed it, and it can never go above the total (ADR-002).
     */
    public function addOneUnit(string $laptopId): void
    {
        $updated = $this->connection->table('laptops.laptop')
            ->where('id', $laptopId)
            ->whereColumn('available_units', '<', 'total_units')
            ->increment('available_units');

        if ($updated === 0) {
            throw new LogicException('Available units can never exceed the total.');
        }
    }

    private function select(): Builder
    {
        return $this->connection->table('laptops.laptop as l')
            ->join('laptops.category as c', 'c.id', '=', 'l.category_id')
            ->select([
                'l.id', 'l.name', 'l.brand', 'l.model_code', 'l.category_id', 'c.name as category_name',
                'l.total_units', 'l.available_units', 'l.photo_key', 'l.deleted_at',
            ]);
    }

    private function remember(object $row): Laptop
    {
        $this->readUnits[$row->id] = (int) $row->available_units;

        return Laptop::restore(
            $row->id,
            $row->name,
            $row->brand,
            $row->model_code,
            new Category($row->category_id, $row->category_name),
            (int) $row->total_units,
            (int) $row->available_units,
            $row->photo_key,
            SqlValues::toInstant($row->deleted_at),
        );
    }

    /** @return array<string, mixed> every column a laptop can change, model code and id excluded */
    private function columns(Laptop $laptop): array
    {
        return [
            'name' => $laptop->name(),
            'brand' => $laptop->brand(),
            'category_id' => $laptop->category()->id,
            'total_units' => $laptop->totalUnits(),
            'available_units' => $laptop->availableUnits(),
            'photo_key' => $laptop->photoKey(),
            'deleted_at' => $laptop->deletedAt() === null ? null : SqlValues::instant($laptop->deletedAt()),
        ];
    }

    private function witness(string $id): int
    {
        return $this->readUnits[$id] ?? throw new LogicException('Saving a laptop that was never read.');
    }
}
