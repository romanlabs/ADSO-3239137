<?php

namespace App\Application\Ports;

interface TransactionRunner
{
    /**
     * Runs the callback inside one transaction and returns its result. Any exception rolls everything back.
     *
     * @template T
     *
     * @param  callable(): T  $work
     * @return T
     */
    public function run(callable $work): mixed;
}
