<?php

namespace App\Domain\Exceptions;

final class LaptopNotAvailable extends DomainError
{
    public const CODE = 'LAPTOP_NOT_AVAILABLE';

    public static function create(string $name, string $field = 'items[0].laptop_id'): self
    {
        return new self("No quedan unidades disponibles de «$name».", [['field' => $field, 'issue' => 'sin unidades']]);
    }
}
