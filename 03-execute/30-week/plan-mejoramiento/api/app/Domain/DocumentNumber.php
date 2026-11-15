<?php

namespace App\Domain;

use App\Domain\Exceptions\ValidationFailed;

final readonly class DocumentNumber
{
    private function __construct(public string $value)
    {
    }

    public static function from(string $raw): self
    {
        $value = trim($raw);

        if (preg_match('/^[A-Za-z0-9]{5,30}$/', $value) !== 1) {
            throw ValidationFailed::field('document_number', 'de 5 a 30 letras o dígitos');
        }

        return new self($value);
    }
}
