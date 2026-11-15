<?php

namespace Tests\Fakes;

use App\Application\Ports\LoanFilter;
use App\Application\Ports\LoanRepository;
use App\Application\Ports\OverdueEntry;
use App\Application\Ports\Page;
use App\Application\Ports\PageRequest;
use App\Application\Ports\StudentStanding;
use App\Domain\Exceptions\ItemAlreadyReturned;
use App\Domain\Laptop;
use App\Domain\Loan;
use App\Domain\LoanItem;
use DateTimeImmutable;
use LogicException;

/**
 * Keeps copies of loans. Units are written through the laptop fake's compare-and-store, so a loan that
 * loses the race for the last unit is never stored (ADR-002).
 */
final class InMemoryLoanRepository implements LoanRepository
{
    /** @var array<string, Loan> */
    private array $loans = [];

    public function __construct(
        private InMemoryLaptopRepository $laptops,
        private InMemoryStudentRepository $students,
    ) {
    }

    public function saveReturn(Loan $loan, string $itemId, Laptop $laptop): void
    {
        $stored = $this->loans[$loan->id] ?? throw new LogicException('Returning an item of a loan that was never stored.');
        $storedItem = $stored->item($itemId);

        if ($storedItem->isReturned()) {
            throw ItemAlreadyReturned::create();
        }

        $this->laptops->addOneUnit($laptop->id);
        $storedItem->markReturned($loan->item($itemId)->returnedAt() ?? throw new LogicException('The item was not marked as returned.'));
    }

    public function search(LoanFilter $filter, PageRequest $page, DateTimeImmutable $now): Page
    {
        $matches = array_filter($this->loans, fn (Loan $loan) => ($filter->loanedFrom === null || $loan->loanedAt >= $filter->loanedFrom)
            && ($filter->loanedBefore === null || $loan->loanedAt < $filter->loanedBefore)
            && ($filter->status === null || $loan->status($now) === $filter->status)
            && ($filter->studentId === null || $loan->studentId === $filter->studentId));

        usort($matches, fn (Loan $a, Loan $b) => [$b->loanedAt, $b->id] <=> [$a->loanedAt, $a->id]);
        $slice = array_slice($matches, $page->offset(), $page->size);

        return new Page(array_map(self::copy(...), $slice), $page->page, $page->size, count($matches));
    }

    public function overdueItems(DateTimeImmutable $now): array
    {
        $entries = [];
        foreach ($this->loans as $loan) {
            foreach ($loan->items() as $item) {
                if ($item->isOverdue($now)) {
                    $student = $this->students->find($loan->studentId) ?? throw new LogicException('Loan without student.');
                    $entries[] = new OverdueEntry($loan->id, $student->fullName, clone $item);
                }
            }
        }

        return $entries;
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
