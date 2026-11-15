<?php

namespace App\Domain\Exceptions;

final class ForbiddenRole extends DomainError
{
    public const CODE = 'FORBIDDEN_ROLE';

    public static function create(): self
    {
        return new self('Tu rol no tiene permiso para esta operación.');
    }
}
