<?php

namespace App\Application\Services;

use App\Application\Ports\Clock;
use App\Application\Ports\LoanFilter;
use App\Application\Ports\LoanRepository;
use App\Application\Ports\Page;
use App\Application\Ports\PageRequest;
use App\Domain\DateRange;
use App\Domain\Exceptions\NotFound;
use App\Domain\Exceptions\ValidationFailed;
use App\Domain\Loan;
use App\Domain\LoanStatus;
use DateTimeImmutable;

/** HU-08: one loan with its items, or a filtered list newest first. */
final readonly class ListLoansService
{
    public function __construct(
        private LoanRepository $loans,
        private Clock $clock,
    ) {
    }

    /** @return Page<LoanView> */
    public function list(?string $from, ?string $to, ?string $status, ?string $studentId, PageRequest $page): Page
    {
        $now = $this->clock->now();
        [$loanedFrom, $loanedBefore] = $this->period($from, $to);
        $filter = new LoanFilter($loanedFrom, $loanedBefore, $this->status($status), self::blankToNull($studentId));

        return $this->loans->search($filter, $page, $now)->map(fn (Loan $loan) => new LoanView($loan, $loan->status($now)));
    }

    public function get(string $id): LoanView
    {
        $loan = $this->loans->find($id) ?? throw NotFound::resource('el préstamo');

        return new LoanView($loan, $loan->status($this->clock->now()));
    }

    /**
     * Both days are inclusive; the result is [first instant, first instant after the last day].
     *
     * @return array{0: ?DateTimeImmutable, 1: ?DateTimeImmutable}
     */
    private function period(?string $from, ?string $to): array
    {
        $fromDay = self::blankToNull($from) === null ? null : CalendarDay::parse('from', $from);
        $toDay = self::blankToNull($to) === null ? null : CalendarDay::parse('to', $to);

        if ($fromDay !== null && $toDay !== null) {
            $range = DateRange::fromDays($fromDay, $toDay);

            return [$range->startsAt(), $range->endsBefore()];
        }

        return [$fromDay, $toDay?->modify('+1 day')];
    }

    private function status(?string $status): ?LoanStatus
    {
        if (self::blankToNull($status) === null) {
            return null;
        }

        return LoanStatus::tryFrom($status) ?? throw ValidationFailed::field('status', 'open, overdue o closed');
    }

    private static function blankToNull(?string $value): ?string
    {
        return $value === null || trim($value) === '' ? null : trim($value);
    }
}
