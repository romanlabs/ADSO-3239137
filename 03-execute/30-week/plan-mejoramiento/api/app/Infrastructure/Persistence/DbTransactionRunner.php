<?php

namespace App\Infrastructure\Persistence;

use App\Application\Ports\TransactionRunner;
use Illuminate\Database\ConnectionInterface;

/** One database transaction per call; any exception rolls back every write made inside it. */
final readonly class DbTransactionRunner implements TransactionRunner
{
    public function __construct(private ConnectionInterface $connection)
    {
    }

    public function run(callable $work): mixed
    {
        return $this->connection->transaction(fn () => $work());
    }
}
