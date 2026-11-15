<?php

namespace App\Domain\Exceptions;

final class StudentLimitExceeded extends DomainError
{
    public const CODE = 'STUDENT_LIMIT_EXCEEDED';

    public static function create(int $unreturned, int $limit): self
    {
        return new self(
            "El estudiante ya tiene {$unreturned} portátil(es) sin devolver; el máximo es {$limit}.",
            [['field' => 'student_id', 'issue' => "sin devolver: {$unreturned}"]],
        );
    }
}
