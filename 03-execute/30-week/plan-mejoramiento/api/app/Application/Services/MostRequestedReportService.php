<?php

namespace App\Application\Services;

use App\Application\Ports\MostRequestedReportReader;
use App\Domain\DateRange;
use App\Domain\Exceptions\ValidationFailed;

/** HU-10: both days are required and inclusive; an empty range is an empty list, not an error. */
final readonly class MostRequestedReportService
{
    public function __construct(private MostRequestedReportReader $reader)
    {
    }

    public function report(?string $from, ?string $to): MostRequestedReport
    {
        $missing = [];
        foreach (['from' => $from, 'to' => $to] as $field => $value) {
            if ($value === null || trim($value) === '') {
                $missing[] = ['field' => $field, 'issue' => 'obligatoria, formato AAAA-MM-DD'];
            }
        }
        if ($missing !== []) {
            throw ValidationFailed::fields($missing);
        }

        $range = DateRange::fromDays(CalendarDay::parse('from', $from), CalendarDay::parse('to', $to));

        return new MostRequestedReport($range, $this->reader->read($range));
    }
}
