<?php

namespace Tests\Fakes;

use App\Application\Services\CreateLaptopService;
use App\Application\Services\CreateStudentService;
use App\Application\Services\LendLaptopService;
use App\Application\Services\UpdateLaptopService;

/**
 * Wires every fake once so application tests read like the scenario they describe.
 * It plays the role of the composition root for tests only.
 */
final class InMemoryApplication
{
    public readonly FixedClock $clock;

    public readonly SequentialIdGenerator $ids;

    public readonly ImmediateTransactionRunner $transactions;

    public readonly InMemoryCategoryRepository $categories;

    public readonly InMemoryLaptopRepository $laptops;

    public readonly InMemoryStudentRepository $students;

    public readonly InMemoryLoanRepository $loans;

    public function __construct(string $now = '2026-11-16T14:15:00Z')
    {
        $this->clock = new FixedClock($now);
        $this->ids = new SequentialIdGenerator;
        $this->transactions = new ImmediateTransactionRunner;
        $this->categories = new InMemoryCategoryRepository;
        $this->laptops = new InMemoryLaptopRepository;
        $this->students = new InMemoryStudentRepository;
        $this->loans = new InMemoryLoanRepository($this->laptops);
    }

    public function createLaptop(): CreateLaptopService
    {
        return new CreateLaptopService($this->laptops, $this->categories, $this->ids);
    }

    public function updateLaptop(): UpdateLaptopService
    {
        return new UpdateLaptopService($this->laptops, $this->categories);
    }

    public function createStudent(): CreateStudentService
    {
        return new CreateStudentService($this->students, $this->ids);
    }

    public function lend(): LendLaptopService
    {
        return new LendLaptopService($this->students, $this->laptops, $this->loans, $this->transactions, $this->ids, $this->clock);
    }
}
