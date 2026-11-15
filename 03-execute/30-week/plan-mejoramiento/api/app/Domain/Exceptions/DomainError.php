<?php

namespace App\Domain\Exceptions;

use RuntimeException;

/**
 * Base class for every business error. The HTTP adapter maps CODE to a status code.
 */
abstract class DomainError extends RuntimeException
{
    public const CODE = 'DOMAIN_ERROR';

    /** @param list<array{field: string, issue: string}> $details */
    public function __construct(string $message, private readonly array $details = [])
    {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return static::CODE;
    }

    /** @return list<array{field: string, issue: string}> */
    public function details(): array
    {
        return $this->details;
    }
}
