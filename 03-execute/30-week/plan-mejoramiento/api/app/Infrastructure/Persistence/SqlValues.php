<?php

namespace App\Infrastructure\Persistence;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Database\QueryException;

/**
 * Small conversions shared by the PostgreSQL adapters. Every instant crosses the boundary in UTC with an
 * explicit offset, so the session time zone of the connection can never shift a due date.
 */
final class SqlValues
{
    private const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    /** PostgreSQL rejects a malformed uuid with an error; the adapters treat it as "no such row" instead. */
    public static function isUuid(?string $value): bool
    {
        return $value !== null && preg_match(self::UUID_PATTERN, $value) === 1;
    }

    public static function instant(DateTimeInterface $instant): string
    {
        return DateTimeImmutable::createFromInterface($instant)
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s.uP');
    }

    public static function toInstant(?string $value): ?DateTimeImmutable
    {
        return $value === null ? null : (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('UTC'));
    }

    /** Escapes the wildcards of ILIKE so the text typed by an operator is matched literally. */
    public static function containsPattern(string $text): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $text).'%';
    }

    public static function isUniqueViolation(QueryException $error): bool
    {
        return $error->getCode() === '23505';
    }
}
