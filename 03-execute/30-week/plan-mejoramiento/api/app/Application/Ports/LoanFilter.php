<?php

namespace App\Application\Ports;

use App\Domain\LoanStatus;
use DateTimeImmutable;

/** Every criterion is optional; `loanedFrom` is inclusive and `loanedBefore` exclusive, both UTC instants. */
final readonly class LoanFilter
{
    public function __construct(
        public ?DateTimeImmutable $loanedFrom = null,
        public ?DateTimeImmutable $loanedBefore = null,
        public ?LoanStatus $status = null,
        public ?string $studentId = null,
    ) {
    }
}
