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
}
