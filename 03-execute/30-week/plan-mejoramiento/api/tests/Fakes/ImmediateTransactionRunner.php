<?php

namespace Tests\Fakes;

use App\Application\Ports\TransactionRunner;

/**
 * The in-memory repositories validate every conditional write before changing anything,
 * so running the work directly behaves like a transaction that rolls back on error.
 */
final class ImmediateTransactionRunner implements TransactionRunner
{
    public int $runs = 0;

    public function run(callable $work): mixed
    {
        $this->runs++;

        return $work();
    }
}
