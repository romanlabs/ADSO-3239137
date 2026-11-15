<?php

namespace App\Domain\Exceptions;

final class StudentHasOverdue extends DomainError
{
    public const CODE = 'STUDENT_HAS_OVERDUE';

    public static function create(): self
    {
        return new self('El estudiante tiene un portátil vencido y no puede recibir otro hasta devolverlo.');
    }
}
