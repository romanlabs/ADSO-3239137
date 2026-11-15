<?php

namespace App\Application\Ports;

/** Unreturned items of one student and how many of them are already overdue (Q7). */
final readonly class StudentStanding
{
    public function __construct(public int $unreturned, public int $overdue)
    {
    }

    public function hasOverdue(): bool
    {
        return $this->overdue > 0;
    }
}
