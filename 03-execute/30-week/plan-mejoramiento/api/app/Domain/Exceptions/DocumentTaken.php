<?php

namespace App\Domain\Exceptions;

final class DocumentTaken extends DomainError
{
    public const CODE = 'DOCUMENT_TAKEN';

    public static function create(string $document): self
    {
        return new self("Ya hay un estudiante con el documento «{$document}».", [['field' => 'document_number', 'issue' => 'repetido']]);
    }
}
