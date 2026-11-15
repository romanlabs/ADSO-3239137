<?php

namespace Tests\Fakes;

use App\Application\Ports\LoanRepository;
use App\Application\Ports\StudentStanding;
use App\Domain\Loan;
use App\Domain\LoanItem;
use DateTimeImmutable;

/**
 * Keeps copies of loans. Units are written through the laptop fake's compare-and-store, so a loan that
 * loses the race for the last unit is never stored (ADR-002).
 */
final class InMemoryLoanRepository implements LoanRepository
{
    /** @var array<string, Loan> */
    private array $loans = [];

    public function __construct(private InMemoryLaptopRepository $laptops)
    {
    }

    public function add(Loan $loan, array $withdrawnLaptops): void
    {
        $this->laptops->compareAndStore($withdrawnLaptops);
        $this->loans[$loan->id] = self::copy($loan);
    }

    public function find(string $id): ?Loan
    {
        return isset($this->loans[$id]) ? self::copy($this->loans[$id]) : null;
    }

    public function standingOf(string $studentId, DateTimeImmutable $now): StudentStanding
    {
        $unreturned = 0;
        $overdue = 0;

        foreach ($this->loans as $loan) {
            if ($loan->studentId !== $studentId) {
                continue;
            }
            foreach ($loan->items() as $item) {
                if (! $item->isReturned()) {
                    $unreturned++;
                    $overdue += $item->isOverdue($now) ? 1 : 0;
                }
            }
        }

        return new StudentStanding($unreturned, $overdue);
    }

    /** @return list<Loan> */
    public function all(): array
    {
        return array_values(array_map(self::copy(...), $this->loans));
    }

    private static function copy(Loan $loan): Loan
    {
        return Loan::restore(
            $loan->id,
            $loan->studentId,
            $loan->loanedByUserId,
            $loan->loanedAt,
            array_map(fn (LoanItem $item) => clone $item, $loan->items()),
        );
    }
}
