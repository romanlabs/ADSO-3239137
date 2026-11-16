<?php

namespace App\Infrastructure\Time;

use App\Application\Ports\Clock;
use DateTimeImmutable;
use DateTimeZone;

/**
 * The real clock in UTC. A non-zero offset in days moves it only when the demo seeder needs past and overdue loans
 * (ADR-005); production never sets it.
 */
final readonly class SystemClock implements Clock
{
    public function __construct(private int $offsetDays = 0)
    {
    }

    public function now(): DateTimeImmutable
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return $this->offsetDays === 0 ? $now : $now->modify(sprintf('%+d days', $this->offsetDays));
    }
}
