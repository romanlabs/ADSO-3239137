<?php

namespace App\Domain;

use App\Domain\Exceptions\ValidationFailed;

final readonly class ModelCode
{
    private function __construct(public string $value)
    {
    }

    public static function from(string $raw): self
    {
        $value = strtoupper(trim($raw));

        if (preg_match('/^[A-Z0-9-]{4,20}$/', $value) !== 1) {
            throw ValidationFailed::field('model_code', 'de 4 a 20 caracteres: letras, dígitos o guion');
        }

        return new self($value);
    }
}
