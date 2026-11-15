<?php

namespace App\Domain;

use App\Domain\Exceptions\DuplicateLaptopInLoan;
use App\Domain\Exceptions\NotFound;
use App\Domain\Exceptions\StudentHasOverdue;
use App\Domain\Exceptions\StudentLimitExceeded;
use DateTimeImmutable;
use LogicException;

/**
 * A loan is an immutable fact: once registered only the return instant of its items can change.
 */
final class Loan
{
    public const LOAN_DAYS = 15;

    public const MAX_UNRETURNED_PER_STUDENT = 1;

    /** @param list<LoanItem> $items */
    private function __construct(
        public readonly string $id,
        public readonly string $studentId,
        public readonly string $loanedByUserId,
        public readonly DateTimeImmutable $loanedAt,
        private array $items,
    ) {
    }

    public static function open(string $id, string $studentId, string $loanedByUserId, DateTimeImmutable $now): self
    {
        return new self($id, $studentId, $loanedByUserId, $now, []);
    }

    /** @param list<LoanItem> $items */
    public static function restore(string $id, string $studentId, string $loanedByUserId, DateTimeImmutable $loanedAt, array $items): self
    {
        return new self($id, $studentId, $loanedByUserId, $loanedAt, $items);
    }

    /**
     * RN-2 and RN-3: checked before any unit is withdrawn.
     *
     * @param  list<string>  $requestedLaptopIds
     */
    public static function ensureStudentMayBorrow(int $unreturned, bool $hasOverdue, array $requestedLaptopIds): void
    {
        foreach ($requestedLaptopIds as $index => $laptopId) {
            if (array_search($laptopId, $requestedLaptopIds, true) !== $index) {
                throw DuplicateLaptopInLoan::create($index);
            }
        }

        if ($hasOverdue) {
            throw StudentHasOverdue::create();
        }

        if ($unreturned + count($requestedLaptopIds) > self::MAX_UNRETURNED_PER_STUDENT) {
            throw StudentLimitExceeded::create($unreturned, self::MAX_UNRETURNED_PER_STUDENT);
        }
    }

    /** Withdrawing the unit and creating the line are one operation (architecture §3). */
    public function addItem(string $itemId, Laptop $laptop): LoanItem
    {
        foreach ($this->items as $index => $item) {
            if ($item->laptopId === $laptop->id) {
                throw DuplicateLaptopInLoan::create($index);
            }
        }

        if (count($this->items) >= self::MAX_UNRETURNED_PER_STUDENT) {
            throw StudentLimitExceeded::create(count($this->items), self::MAX_UNRETURNED_PER_STUDENT);
        }

        $laptop->withdraw();
        $item = LoanItem::freeze($itemId, $laptop, $this->loanedAt->modify('+'.self::LOAN_DAYS.' days'));
        $this->items[] = $item;

        return $item;
    }

    public function returnItem(string $itemId, Laptop $laptop, DateTimeImmutable $now): LoanItem
    {
        $item = $this->item($itemId);

        if ($item->laptopId !== $laptop->id) {
            throw new LogicException('The laptop does not belong to this loan item.');
        }

        $item->markReturned($now);
        $laptop->restock();

        return $item;
    }

    public function item(string $itemId): LoanItem
    {
        foreach ($this->items as $item) {
            if ($item->id === $itemId) {
                return $item;
            }
        }

        throw NotFound::resource('la línea del préstamo');
    }

    public function status(DateTimeImmutable $now): LoanStatus
    {
        $pending = array_filter($this->items, fn (LoanItem $item) => ! $item->isReturned());

        if ($pending === []) {
            return LoanStatus::Closed;
        }

        foreach ($pending as $item) {
            if ($item->isOverdue($now)) {
                return LoanStatus::Overdue;
            }
        }

        return LoanStatus::Open;
    }

    /** @return list<LoanItem> */
    public function items(): array
    {
        return $this->items;
    }
}
