<?php

namespace Tests\Fakes;

use App\Application\Ports\Page;
use App\Application\Ports\PageRequest;
use App\Application\Ports\StudentRepository;
use App\Domain\Exceptions\DocumentTaken;
use App\Domain\Student;

final class InMemoryStudentRepository implements StudentRepository
{
    /** @var array<string, Student> */
    private array $students = [];

    public function find(string $id): ?Student
    {
        return $this->students[$id] ?? null;
    }

    public function documentExists(string $documentNumber): bool
    {
        foreach ($this->students as $student) {
            if ($student->documentNumber->value === $documentNumber) {
                return true;
            }
        }

        return false;
    }

    public function add(Student $student): void
    {
        if ($this->documentExists($student->documentNumber->value)) {
            throw DocumentTaken::create($student->documentNumber->value);
        }

        $this->students[$student->id] = $student;
    }

    public function search(?string $text, PageRequest $page): Page
    {
        $matches = array_filter($this->students, fn (Student $student) => $text === null
            || mb_stripos($student->fullName, $text) !== false
            || mb_stripos($student->documentNumber->value, $text) !== false);

        usort($matches, fn (Student $a, Student $b) => strcmp(mb_strtolower($a->fullName), mb_strtolower($b->fullName)) ?: strcmp($a->id, $b->id));

        return new Page(array_slice($matches, $page->offset(), $page->size), $page->page, $page->size, count($matches));
    }
}
