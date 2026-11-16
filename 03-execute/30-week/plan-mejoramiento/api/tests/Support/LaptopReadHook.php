<?php

namespace Tests\Support;

use App\Application\Ports\LaptopRepository;
use App\Application\Ports\Page;
use App\Application\Ports\PageRequest;
use App\Domain\Laptop;

/**
 * Delegates to a real laptop adapter and runs a callback once, right after the first batch read. It lets a test put
 * another operator's whole request between "this request read the units" and "this request writes them".
 */
final class LaptopReadHook implements LaptopRepository
{
    /** @var (callable(): void)|null */
    private $afterFirstRead;

    /** @param callable(): void $afterFirstRead */
    public function __construct(private readonly LaptopRepository $inner, callable $afterFirstRead)
    {
        $this->afterFirstRead = $afterFirstRead;
    }

    public function findMany(array $ids): array
    {
        $found = $this->inner->findMany($ids);

        if ($this->afterFirstRead !== null) {
            $callback = $this->afterFirstRead;
            $this->afterFirstRead = null;
            $callback();
        }

        return $found;
    }

    public function find(string $id): ?Laptop
    {
        return $this->inner->find($id);
    }

    public function findIncludingRetired(string $id): ?Laptop
    {
        return $this->inner->findIncludingRetired($id);
    }

    public function search(?string $text, ?string $categoryId, PageRequest $page): Page
    {
        return $this->inner->search($text, $categoryId, $page);
    }

    public function modelCodeInUse(string $modelCode): bool
    {
        return $this->inner->modelCodeInUse($modelCode);
    }

    public function add(Laptop $laptop): void
    {
        $this->inner->add($laptop);
    }

    public function save(Laptop $laptop): void
    {
        $this->inner->save($laptop);
    }
}
