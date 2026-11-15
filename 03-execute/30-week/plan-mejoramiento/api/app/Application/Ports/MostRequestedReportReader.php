<?php

namespace App\Application\Ports;

use App\Domain\DateRange;

/**
 * Read port for HU-10 (ADR-004). The adapter computes it with one aggregated query grouped by the values
 * frozen in each loan item, never by the live catalog, ordered by times requested and then by name.
 */
interface MostRequestedReportReader
{
    /** @return list<MostRequestedRow> */
    public function read(DateRange $range): array;
}
