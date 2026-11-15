<?php

namespace App\Domain\Exceptions;

final class ItemAlreadyReturned extends DomainError
{
    public const CODE = 'ITEM_ALREADY_RETURNED';

    public static function create(): self
    {
        return new self('Esta línea del préstamo ya fue devuelta.');
    }
}
