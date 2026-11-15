<?php

namespace App\Application\Ports;

use App\Domain\Laptop;

interface LaptopRepository
{
    /** Active laptop by id; retired laptops are reported as missing (Q2, CA-04.3). */
    public function find(string $id): ?Laptop;

    /** Any laptop by id, retired or not; used when returning an item of a past loan. */
    public function findIncludingRetired(string $id): ?Laptop;

    /**
     * Active laptops for the given ids in one query (Q3). Missing or retired ids are absent from the result.
     *
     * @param  list<string>  $ids
     * @return array<string, Laptop> keyed by id
     */
    public function findMany(array $ids): array;

    /**
     * Partial text on name or brand (case-insensitive), optional category, active only,
     * ordered by name ignoring case and then by id (Q1).
     *
     * @return Page<Laptop>
     */
    public function search(?string $text, ?string $categoryId, PageRequest $page): Page;

    /** Model codes are unique among active laptops only (CA-03.2). */
    public function modelCodeInUse(string $modelCode): bool;

    /** Throws ModelCodeTaken if the partial unique index rejects the row. */
    public function add(Laptop $laptop): void;

    /**
     * Stores name, brand, category, totals, photo key and retirement. The adapter writes the available
     * units only if they still hold the value that was read (ADR-002) and throws LaptopNotAvailable otherwise.
     */
    public function save(Laptop $laptop): void;
}
