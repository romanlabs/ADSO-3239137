<?php

namespace App\Application\Ports;

interface IdGenerator
{
    /** Returns a new UUID v4 as text. */
    public function next(): string;
}
