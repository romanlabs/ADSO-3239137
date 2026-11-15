<?php

namespace App\Domain\Exceptions;

final class DuplicateLaptopInLoan extends DomainError
{
    public const CODE = 'DUPLICATE_LAPTOP_IN_LOAN';

    public static function create(int $index): self
    {
        return new self('El mismo portátil aparece dos veces en el préstamo.', [['field' => "items[{$index}].laptop_id", 'issue' => 'repetido']]);
    }
}
