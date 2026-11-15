<?php

namespace App\Application\Ports;

use App\Domain\Student;

interface StudentRepository
{
    public function find(string $id): ?Student;

    public function documentExists(string $documentNumber): bool;

    /** Throws DocumentTaken if the unique index rejects the row. */
    public function add(Student $student): void;

    /**
     * Partial text on full name or document number, ordered by name ignoring case and then by id.
     *
     * @return Page<Student>
     */
    public function search(?string $text, PageRequest $page): Page;
}
