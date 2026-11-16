<?php

namespace App\Infrastructure\Persistence;

use App\Application\Ports\IdGenerator;

/** UUID version 4 from the operating system's random source (RFC 9562). */
final class RandomUuidGenerator implements IdGenerator
{
    public function next(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
