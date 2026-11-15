<?php

namespace App\Domain;

use App\Domain\Exceptions\InvalidDateRange;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Inclusive range of calendar days in UTC.
 */
final readonly class DateRange
{
    private function __construct(public DateTimeImmutable $from, public DateTimeImmutable $to)
    {
    }

    public static function fromDays(DateTimeImmutable $from, DateTimeImmutable $to): self
    {
        $utc = new DateTimeZone('UTC');
        $start = $from->setTimezone($utc)->setTime(0, 0);
        $end = $to->setTimezone($utc)->setTime(0, 0);

        if ($end < $start) {
            throw InvalidDateRange::create();
        }

        return new self($start, $end);
    }

    public function startsAt(): DateTimeImmutable
    {
        return $this->from;
    }

    /** First instant after the range, so the last day is included. */
    public function endsBefore(): DateTimeImmutable
    {
        return $this->to->modify('+1 day');
    }
}
