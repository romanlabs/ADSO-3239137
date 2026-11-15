<?php

namespace App\Application\Services;

use App\Domain\Student;

final readonly class StudentDetail
{
    public function __construct(public Student $student, public int $activeLoans)
    {
    }
}
