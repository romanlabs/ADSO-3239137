<?php

namespace App\Application\Ports;

/** One row per laptop and frozen name and category. No student or assistant data (CA-10.3). */
final readonly class MostRequestedRow
{
    public function __construct(
        public string $laptopId,
        public string $laptopName,
        public string $categoryName,
        public int $timesRequested,
    ) {
    }
}
