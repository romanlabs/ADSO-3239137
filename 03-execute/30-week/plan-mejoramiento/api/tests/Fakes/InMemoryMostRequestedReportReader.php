<?php

namespace Tests\Fakes;

use App\Application\Ports\MostRequestedReportReader;
use App\Application\Ports\MostRequestedRow;
use App\Domain\DateRange;

/** Groups the stored loan items by their frozen values, the same way the SQL query of ADR-004 does. */
final class InMemoryMostRequestedReportReader implements MostRequestedReportReader
{
    public function __construct(private InMemoryLoanRepository $loans)
    {
    }

    public function read(DateRange $range): array
    {
        $groups = [];
        foreach ($this->loans->all() as $loan) {
            if ($loan->loanedAt < $range->startsAt() || $loan->loanedAt >= $range->endsBefore()) {
                continue;
            }
            foreach ($loan->items() as $item) {
                $key = json_encode([$item->laptopId, $item->laptopName, $item->categoryName]);
                $groups[$key] ??= ['item' => $item, 'count' => 0];
                $groups[$key]['count']++;
            }
        }

        $rows = array_map(
            fn (array $group) => new MostRequestedRow($group['item']->laptopId, $group['item']->laptopName, $group['item']->categoryName, $group['count']),
            array_values($groups),
        );
        usort($rows, fn (MostRequestedRow $a, MostRequestedRow $b) => [$b->timesRequested, $a->laptopName, $a->categoryName] <=> [$a->timesRequested, $b->laptopName, $b->categoryName]);

        return $rows;
    }
}
