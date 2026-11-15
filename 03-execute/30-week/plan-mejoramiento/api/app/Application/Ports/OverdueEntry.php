<?php

namespace App\Application\Ports;

use App\Domain\LoanItem;

final readonly class OverdueEntry
{
    public function __construct(public string $loanId, public string $studentName, public LoanItem $item)
    {
    }
}
