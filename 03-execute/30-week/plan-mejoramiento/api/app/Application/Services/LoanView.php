<?php

namespace App\Application\Services;

use App\Domain\Loan;
use App\Domain\LoanStatus;

/** A loan together with its status computed at read time (CA-08.3). */
final readonly class LoanView
{
    public function __construct(public Loan $loan, public LoanStatus $status)
    {
    }
}
