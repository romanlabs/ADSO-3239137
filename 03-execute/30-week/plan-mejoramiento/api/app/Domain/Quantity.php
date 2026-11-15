<?php

namespace App\Domain;

use App\Domain\Exceptions\ValidationFailed;

final readonly class Quantity
{
    private function __construct(public int $value)
    {
    }

    public static function from(int $value, string $field = 'total_units'): self
    {
        if ($value < 1) {
            throw ValidationFailed::field($field, 'debe ser mayor o igual a 1');
        }

        return new self($value);
    }
}
