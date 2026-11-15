<?php

namespace App\Application\Ports;

/**
 * @template T
 */
final readonly class Page
{
    /** @param list<T> $items */
    public function __construct(public array $items, public int $page, public int $size, public int $total)
    {
    }

    /**
     * @template U
     *
     * @param  callable(T): U  $mapper
     * @return Page<U>
     */
    public function map(callable $mapper): self
    {
        return new self(array_map($mapper, $this->items), $this->page, $this->size, $this->total);
    }
}
