<?php

namespace App\Application\Services;

use App\Application\Ports\IdGenerator;
use App\Application\Ports\StudentRepository;
use App\Domain\DocumentNumber;
use App\Domain\Exceptions\DocumentTaken;
use App\Domain\Student;

/** CA-05.1: full name and a unique document number. */
final readonly class CreateStudentService
{
    public function __construct(
        private StudentRepository $students,
        private IdGenerator $ids,
    ) {
    }

    public function create(string $fullName, string $documentNumber): Student
    {
        $student = Student::register($this->ids->next(), $fullName, DocumentNumber::from($documentNumber));

        if ($this->students->documentExists($student->documentNumber->value)) {
            throw DocumentTaken::create($student->documentNumber->value);
        }

        $this->students->add($student);

        return $student;
    }
}
