<?php

namespace App\Application\Services;

use App\Application\Ports\Clock;
use App\Application\Ports\LaptopRepository;
use App\Domain\Exceptions\LaptopHasOpenLoans;
use App\Domain\Exceptions\NotFound;

/** CA-03.5: soft delete; a laptop with units still on loan cannot be retired. */
final readonly class RetireLaptopService
{
    public function __construct(
        private LaptopRepository $laptops,
        private Clock $clock,
    ) {
    }

    public function retire(string $id): void
    {
        $laptop = $this->laptops->find($id) ?? throw NotFound::resource('el portátil');

        if ($laptop->hasUnitsOnLoan()) {
            throw LaptopHasOpenLoans::create($laptop->name());
        }

        $laptop->retire($this->clock->now());
        $this->laptops->save($laptop);
    }
}
