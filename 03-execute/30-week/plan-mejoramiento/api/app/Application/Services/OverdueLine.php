<?php

namespace App\Application\Services;

use DateTimeImmutable;

final readonly class OverdueLine
{
    public function __construct(
        public string $loanId,
        public string $itemId,
        public string $laptopName,
        public string $studentName,
        public DateTimeImmutable $dueAt,
        public int $daysLate,
    ) {
    }
}
