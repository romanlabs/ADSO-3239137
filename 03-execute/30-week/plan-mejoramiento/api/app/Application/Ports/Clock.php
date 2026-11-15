<?php

namespace App\Application\Ports;

use DateTimeImmutable;

/**
 * The only source of "now" for the core (ADR-005). Always returns a UTC instant.
 */
interface Clock
{
    public function now(): DateTimeImmutable;
}
