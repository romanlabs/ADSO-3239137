<?php

namespace App\Domain\Exceptions;

final class NotFound extends DomainError
{
    public const CODE = 'NOT_FOUND';

    public static function resource(string $what): self
    {
        return new self("No se encontró $what.");
    }
}
