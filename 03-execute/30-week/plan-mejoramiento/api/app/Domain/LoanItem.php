<?php

namespace App\Domain;

use App\Domain\Exceptions\ItemAlreadyReturned;
use DateTimeImmutable;

/**
 * One laptop inside a loan. Name, model code and category are frozen at lending time.
 */
final class LoanItem
{
    public function __construct(
        public readonly string $id,
        public readonly string $laptopId,
        public readonly string $laptopName,
        public readonly string $modelCode,
        public readonly string $categoryName,
        public readonly DateTimeImmutable $dueAt,
        private ?DateTimeImmutable $returnedAt = null,
    ) {
    }

    public static function freeze(string $id, Laptop $laptop, DateTimeImmutable $dueAt): self
    {
        return new self($id, $laptop->id, $laptop->name(), $laptop->modelCode->value, $laptop->category()->name, $dueAt);
    }

    public function markReturned(DateTimeImmutable $now): void
    {
        if ($this->returnedAt !== null) {
            throw ItemAlreadyReturned::create();
        }

        $this->returnedAt = $now;
    }

    public function isReturned(): bool
    {
        return $this->returnedAt !== null;
    }

    public function isOverdue(DateTimeImmutable $now): bool
    {
        return $this->returnedAt === null && $this->dueAt < $now;
    }

    public function daysLate(DateTimeImmutable $now): int
    {
        if (! $this->isOverdue($now)) {
            return 0;
        }

        return max(1, (int) $this->dueAt->diff($now)->days);
    }

    public function returnedAt(): ?DateTimeImmutable
    {
        return $this->returnedAt;
    }
}
