<?php

namespace App\Domain\Exceptions;

final class ValidationFailed extends DomainError
{
    public const CODE = 'VALIDATION_ERROR';

    public static function field(string $field, string $issue): self
    {
        return new self('Hay datos inválidos en la petición.', [['field' => $field, 'issue' => $issue]]);
    }

    /** @param list<array{field: string, issue: string}> $details */
    public static function fields(array $details): self
    {
        return new self('Hay datos inválidos en la petición.', $details);
    }
}
