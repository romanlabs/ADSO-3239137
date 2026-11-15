<?php

namespace App\Application\Services;

use App\Application\Ports\Clock;
use App\Application\Ports\IdGenerator;
use App\Application\Ports\LaptopRepository;
use App\Application\Ports\LoanRepository;
use App\Application\Ports\StudentRepository;
use App\Application\Ports\TransactionRunner;
use App\Domain\Exceptions\DuplicateLaptopInLoan;
use App\Domain\Exceptions\LaptopNotAvailable;
use App\Domain\Exceptions\NotFound;
use App\Domain\Exceptions\ValidationFailed;
use App\Domain\Loan;

/**
 * HU-06. Checks follow the order fixed by the contract (§3.6): request shape, repeated laptop, missing
 * student or laptop, overdue items, limit, and finally units. Nothing is stored unless every check passes.
 */
final readonly class LendLaptopService
{
    public function __construct(
        private StudentRepository $students,
        private LaptopRepository $laptops,
        private LoanRepository $loans,
        private TransactionRunner $transactions,
        private IdGenerator $ids,
        private Clock $clock,
    ) {
    }

    /** @param list<string> $laptopIds */
    public function lend(string $studentId, array $laptopIds, string $userId): Loan
    {
        $laptopIds = array_values($laptopIds);

        if ($laptopIds === []) {
            throw ValidationFailed::field('items', 'debe traer al menos un portátil');
        }

        foreach ($laptopIds as $index => $laptopId) {
            if (array_search($laptopId, $laptopIds, true) !== $index) {
                throw DuplicateLaptopInLoan::create($index);
            }
        }

        $this->students->find($studentId) ?? throw NotFound::resource('el estudiante');

        $found = $this->laptops->findMany($laptopIds);
        $laptops = [];
        foreach ($laptopIds as $laptopId) {
            $laptops[] = $found[$laptopId] ?? throw NotFound::resource('el portátil');
        }

        $now = $this->clock->now();
        $standing = $this->loans->standingOf($studentId, $now);
        Loan::ensureStudentMayBorrow($standing->unreturned, $standing->hasOverdue(), $laptopIds);

        $loan = Loan::open($this->ids->next(), $studentId, $userId, $now);
        foreach ($laptops as $index => $laptop) {
            if ($laptop->availableUnits() === 0) {
                throw LaptopNotAvailable::create($laptop->name(), "items[{$index}].laptop_id");
            }
            $loan->addItem($this->ids->next(), $laptop);
        }

        $this->transactions->run(fn () => $this->loans->add($loan, $laptops));

        return $loan;
    }
}
