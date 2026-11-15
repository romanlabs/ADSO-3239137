<?php

namespace App\Application\Services;

use App\Application\Ports\Clock;
use App\Application\Ports\LoanRepository;
use App\Application\Ports\OverdueEntry;

/** HU-09: unreturned items past their due instant, most days late first. Days late are computed, never stored. */
final readonly class OverdueService
{
    public function __construct(
        private LoanRepository $loans,
        private Clock $clock,
    ) {
    }

    /** @return list<OverdueLine> */
    public function list(): array
    {
        $now = $this->clock->now();

        $lines = array_map(fn (OverdueEntry $entry) => new OverdueLine(
            $entry->loanId,
            $entry->item->id,
            $entry->item->laptopName,
            $entry->studentName,
            $entry->item->dueAt,
            $entry->item->daysLate($now),
        ), $this->loans->overdueItems($now));

        usort($lines, fn (OverdueLine $a, OverdueLine $b) => [$b->daysLate, $a->dueAt, $a->itemId] <=> [$a->daysLate, $b->dueAt, $b->itemId]);

        return $lines;
    }
}
