<?php

namespace App\Application\Services;

use App\Application\Ports\Clock;
use App\Application\Ports\LaptopRepository;
use App\Application\Ports\LoanRepository;
use App\Application\Ports\TransactionRunner;
use App\Domain\Exceptions\ItemAlreadyReturned;
use App\Domain\Exceptions\NotFound;
use App\Domain\LoanItem;
use LogicException;

/** HU-07: one item at a time; a second return is a conflict and never adds a unit twice. */
final readonly class ReturnItemService
{
    public function __construct(
        private LoanRepository $loans,
        private LaptopRepository $laptops,
        private TransactionRunner $transactions,
        private Clock $clock,
    ) {
    }

    public function returnItem(string $loanId, string $itemId): LoanItem
    {
        $loan = $this->loans->find($loanId) ?? throw NotFound::resource('el préstamo');
        $item = $loan->item($itemId);

        if ($item->isReturned()) {
            throw ItemAlreadyReturned::create();
        }

        $laptop = $this->laptops->findIncludingRetired($item->laptopId)
            ?? throw new LogicException('A loan item always points to a stored laptop.');

        $returned = $loan->returnItem($itemId, $laptop, $this->clock->now());
        $this->transactions->run(fn () => $this->loans->saveReturn($loan, $itemId, $laptop));

        return $returned;
    }
}
