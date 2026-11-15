<?php

namespace Tests\Fakes;

use App\Application\Ports\Clock;
use DateTimeImmutable;
use DateTimeZone;

final class FixedClock implements Clock
{
    private DateTimeImmutable $now;

    public function __construct(string $instant = '2026-11-16T14:15:00Z')
    {
        $this->now = (new DateTimeImmutable($instant))->setTimezone(new DateTimeZone('UTC'));
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function advance(string $modifier): void
    {
        $this->now = $this->now->modify($modifier);
    }
}
