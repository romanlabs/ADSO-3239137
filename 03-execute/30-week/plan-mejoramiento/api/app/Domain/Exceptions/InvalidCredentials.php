<?php

namespace App\Domain\Exceptions;

final class InvalidCredentials extends DomainError
{
    public const CODE = 'INVALID_CREDENTIALS';

    public static function create(): self
    {
        return new self('Usuario o contraseña incorrectos.');
    }

    public static function missingToken(): self
    {
        return new self('La sesión no es válida o expiró. Inicia sesión de nuevo.');
    }
}
