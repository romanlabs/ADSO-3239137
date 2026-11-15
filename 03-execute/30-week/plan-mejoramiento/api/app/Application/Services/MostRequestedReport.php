<?php

namespace App\Application\Services;

use App\Application\Ports\MostRequestedRow;
use App\Domain\DateRange;

final readonly class MostRequestedReport
{
    /** @param list<MostRequestedRow> $rows */
    public function __construct(public DateRange $range, public array $rows)
    {
    }
}
