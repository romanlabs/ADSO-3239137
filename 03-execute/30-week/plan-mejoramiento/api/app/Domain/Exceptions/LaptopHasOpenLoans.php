<?php

namespace App\Domain\Exceptions;

final class LaptopHasOpenLoans extends DomainError
{
    public const CODE = 'LAPTOP_HAS_OPEN_LOANS';

    public static function create(string $name): self
    {
        return new self("«$name» tiene préstamos sin devolver y no se puede dar de baja.");
    }
}
