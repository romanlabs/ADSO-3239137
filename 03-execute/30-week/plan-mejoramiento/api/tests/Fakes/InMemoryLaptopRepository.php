<?php

namespace Tests\Fakes;

use App\Application\Ports\LaptopRepository;
use App\Application\Ports\Page;
use App\Application\Ports\PageRequest;
use App\Domain\Exceptions\LaptopNotAvailable;
use App\Domain\Exceptions\ModelCodeTaken;
use App\Domain\Laptop;
use LogicException;

/**
 * Stores copies, so a service that changes a laptop and then fails leaves nothing behind.
 * Like the PostgreSQL adapter (ADR-002), it remembers the available units it handed out and only
 * writes if the stored value is still the same.
 */
final class InMemoryLaptopRepository implements LaptopRepository
{
    /** @var array<string, Laptop> */
    private array $laptops = [];

    /** @var array<string, int> available units as last read, keyed by id */
    private array $readUnits = [];

    /** @var array<int, callable(string): bool> each returns true once it has fired */
    private array $afterRead = [];

    public function find(string $id): ?Laptop
    {
        $laptop = $this->findIncludingRetired($id);

        return $laptop === null || $laptop->isRetired() ? null : $laptop;
    }

    public function findIncludingRetired(string $id): ?Laptop
    {
        if (! isset($this->laptops[$id])) {
            return null;
        }

        $copy = clone $this->laptops[$id];
        $this->readUnits[$id] = $copy->availableUnits();
        $this->runAfterRead($id);

        return $copy;
    }

    public function findMany(array $ids): array
    {
        $found = [];
        foreach ($ids as $id) {
            $laptop = $this->find($id);
            if ($laptop !== null) {
                $found[$id] = $laptop;
            }
        }

        return $found;
    }

    public function search(?string $text, ?string $categoryId, PageRequest $page): Page
    {
        $matches = array_filter($this->laptops, function (Laptop $laptop) use ($text, $categoryId) {
            if ($laptop->isRetired()) {
                return false;
            }
            if ($categoryId !== null && $laptop->category()->id !== $categoryId) {
                return false;
            }
            if ($text === null) {
                return true;
            }

            return mb_stripos($laptop->name(), $text) !== false || mb_stripos($laptop->brand(), $text) !== false;
        });

        usort($matches, fn (Laptop $a, Laptop $b) => strcmp(mb_strtolower($a->name()), mb_strtolower($b->name())) ?: strcmp($a->id, $b->id));
        $slice = array_slice($matches, $page->offset(), $page->size);

        return new Page(array_map(fn (Laptop $laptop) => clone $laptop, $slice), $page->page, $page->size, count($matches));
    }

    public function modelCodeInUse(string $modelCode): bool
    {
        foreach ($this->laptops as $laptop) {
            if (! $laptop->isRetired() && $laptop->modelCode->value === $modelCode) {
                return true;
            }
        }

        return false;
    }

    public function add(Laptop $laptop): void
    {
        if ($this->modelCodeInUse($laptop->modelCode->value)) {
            throw ModelCodeTaken::create($laptop->modelCode->value);
        }

        $this->laptops[$laptop->id] = clone $laptop;
    }

    public function save(Laptop $laptop): void
    {
        $this->compareAndStore([$laptop]);
    }

    /**
     * Checks every laptop first and only then writes, so a lost race stores nothing.
     *
     * @param  list<Laptop>  $laptops
     */
    public function compareAndStore(array $laptops): void
    {
        foreach ($laptops as $index => $laptop) {
            $stored = $this->laptops[$laptop->id] ?? throw new LogicException('Saving a laptop that was never added.');
            $read = $this->readUnits[$laptop->id] ?? throw new LogicException('Saving a laptop that was never read.');

            if ($stored->availableUnits() !== $read) {
                throw LaptopNotAvailable::create($laptop->name(), "items[{$index}].laptop_id");
            }
        }

        foreach ($laptops as $laptop) {
            $this->laptops[$laptop->id] = clone $laptop;
            $this->readUnits[$laptop->id] = $laptop->availableUnits();
        }
    }

    /** Simulates another operator lending one unit right after this request read the laptop. */
    public function anotherOperatorTakesAUnitAfterNextRead(string $laptopId): void
    {
        $this->afterRead[] = function (string $readId) use ($laptopId): bool {
            if ($readId !== $laptopId) {
                return false;
            }
            $this->laptops[$laptopId]->withdraw();

            return true;
        };
    }

    /** Test helper: the stored state, without recording a read. */
    public function stored(string $id): Laptop
    {
        return clone $this->laptops[$id];
    }

    private function runAfterRead(string $id): void
    {
        foreach ($this->afterRead as $index => $sideEffect) {
            if ($sideEffect($id)) {
                unset($this->afterRead[$index]);
            }
        }
    }
}
