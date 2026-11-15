<?php

namespace App\Application\Ports;

use App\Domain\Laptop;
use App\Domain\Loan;
use DateTimeImmutable;

interface LoanRepository
{
    /**
     * Stores the loan, its items and the withdrawn units in the caller's transaction. Each laptop is written
     * only if its available units still hold the value that was read (ADR-002); when another operator won,
     * it throws LaptopNotAvailable and nothing must be committed.
     *
     * @param  list<Laptop>  $withdrawnLaptops
     */
    public function add(Loan $loan, array $withdrawnLaptops): void;

    /** Loan with its items (Q5). */
    public function find(string $id): ?Loan;

    /** Unreturned items of a student and how many are overdue at the given instant (Q7). */
    public function standingOf(string $studentId, DateTimeImmutable $now): StudentStanding;

    /**
     * Stores the return instant of one item only if it was still unreturned (`WHERE returned_at IS NULL`),
     * otherwise throws ItemAlreadyReturned. In the same transaction it adds exactly one available unit to
     * the laptop with an atomic increment that can never go above the total (ADR-002, CA-07.2).
     */
    public function saveReturn(Loan $loan, string $itemId, Laptop $laptop): void;

    /**
     * Loans filtered by date range, computed status and student, newest first (Q6).
     *
     * @return Page<Loan>
     */
    public function search(LoanFilter $filter, PageRequest $page, DateTimeImmutable $now): Page;

    /**
     * Unreturned items whose due instant is before now, with the student's name (Q8).
     *
     * @return list<OverdueEntry>
     */
    public function overdueItems(DateTimeImmutable $now): array;
}
