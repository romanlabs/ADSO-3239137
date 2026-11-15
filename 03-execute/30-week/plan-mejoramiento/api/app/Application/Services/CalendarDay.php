<?php

namespace App\Application\Services;

use App\Domain\Exceptions\ValidationFailed;
use DateTimeImmutable;
use DateTimeZone;

/** Parses the `YYYY-MM-DD` query values of the contract into midnight UTC. */
final class CalendarDay
{
    public static function parse(string $field, string $value): DateTimeImmutable
    {
        $day = DateTimeImmutable::createFromFormat('!Y-m-d', trim($value), new DateTimeZone('UTC'));

        if ($day === false || $day->format('Y-m-d') !== trim($value)) {
            throw ValidationFailed::field($field, 'fecha con formato AAAA-MM-DD');
        }

        return $day;
    }
}
