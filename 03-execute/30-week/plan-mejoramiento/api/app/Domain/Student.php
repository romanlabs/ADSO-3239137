<?php

namespace App\Domain;

use App\Domain\Exceptions\ValidationFailed;

/**
 * A student receives laptops but never signs in. Students are never deleted (CA-05.3).
 */
final readonly class Student
{
    private function __construct(
        public string $id,
        public string $fullName,
        public DocumentNumber $documentNumber,
    ) {
    }

    public static function register(string $id, string $fullName, DocumentNumber $documentNumber): self
    {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName) ?? '');

        if ($fullName === '' || mb_strlen($fullName) > 200) {
            throw ValidationFailed::field('full_name', 'obligatorio, máximo 200 caracteres');
        }

        return new self($id, $fullName, $documentNumber);
    }

    public static function restore(string $id, string $fullName, string $documentNumber): self
    {
        return new self($id, $fullName, DocumentNumber::from($documentNumber));
    }
}
