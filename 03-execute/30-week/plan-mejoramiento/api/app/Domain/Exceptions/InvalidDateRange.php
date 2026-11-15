<?php

namespace App\Domain\Exceptions;

final class InvalidDateRange extends DomainError
{
    public const CODE = 'INVALID_DATE_RANGE';

    public static function create(): self
    {
        return new self('La fecha final no puede ser anterior a la inicial.', [['field' => 'to', 'issue' => 'anterior a from']]);
    }
}
