<?php

namespace App\Application\Services;

use App\Application\Ports\Clock;
use App\Application\Ports\LoanRepository;
use App\Application\Ports\Page;
use App\Application\Ports\PageRequest;
use App\Application\Ports\StudentRepository;
use App\Domain\Exceptions\NotFound;
use App\Domain\Student;

/** CA-05.2: list and search students; the detail carries the count of unreturned items. */
final readonly class SearchStudentsService
{
    public function __construct(
        private StudentRepository $students,
        private LoanRepository $loans,
        private Clock $clock,
    ) {
    }

    /** @return Page<Student> */
    public function search(?string $text, PageRequest $page): Page
    {
        $text = $text === null ? null : trim($text);

        return $this->students->search($text === '' ? null : $text, $page);
    }

    public function get(string $id): StudentDetail
    {
        $student = $this->students->find($id) ?? throw NotFound::resource('el estudiante');

        return new StudentDetail($student, $this->loans->standingOf($id, $this->clock->now())->unreturned);
    }
}
