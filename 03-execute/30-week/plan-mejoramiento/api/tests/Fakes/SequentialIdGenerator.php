<?php

namespace Tests\Fakes;

use App\Application\Ports\IdGenerator;

/** Predictable UUID-shaped ids so assertions can name them. */
final class SequentialIdGenerator implements IdGenerator
{
    private int $counter = 0;

    public function next(): string
    {
        $this->counter++;

        return sprintf('00000000-0000-4000-8000-%012d', $this->counter);
    }
}
