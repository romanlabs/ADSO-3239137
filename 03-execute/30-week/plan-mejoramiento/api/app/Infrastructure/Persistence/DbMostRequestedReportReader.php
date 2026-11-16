<?php

namespace App\Infrastructure\Persistence;

use App\Application\Ports\MostRequestedReportReader;
use App\Application\Ports\MostRequestedRow;
use App\Domain\DateRange;
use Illuminate\Database\ConnectionInterface;

/**
 * Q9 and ADR-004: one aggregated query over the values frozen in each loan item, never the live catalog, so renaming a
 * laptop or a category cannot change a closed period. Students and assistants are not selected (CA-10.3).
 */
final readonly class DbMostRequestedReportReader implements MostRequestedReportReader
{
    public function __construct(private ConnectionInterface $connection)
    {
    }

    public function read(DateRange $range): array
    {
        return $this->connection->table('laptops.loan_item as i')
            ->join('laptops.loan as l', 'l.id', '=', 'i.loan_id')
            ->where('l.loaned_at', '>=', SqlValues::instant($range->startsAt()))
            ->where('l.loaned_at', '<', SqlValues::instant($range->endsBefore()))
            ->groupBy('i.laptop_id', 'i.laptop_name', 'i.category_name')
            ->selectRaw('i.laptop_id, i.laptop_name, i.category_name, count(*) AS times_requested')
            ->orderByDesc('times_requested')
            ->orderByRaw('i.laptop_name COLLATE "C"')
            ->orderByRaw('i.category_name COLLATE "C"')
            ->get()
            ->map(fn (object $row) => new MostRequestedRow($row->laptop_id, $row->laptop_name, $row->category_name, (int) $row->times_requested))
            ->all();
    }
}
