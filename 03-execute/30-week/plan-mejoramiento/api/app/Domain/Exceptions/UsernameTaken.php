<?php

namespace App\Domain\Exceptions;

final class UsernameTaken extends DomainError
{
    public const CODE = 'USERNAME_TAKEN';

    public static function create(string $username): self
    {
        return new self("El usuario «$username» ya existe.", [['field' => 'username', 'issue' => 'repetido']]);
    }
}
