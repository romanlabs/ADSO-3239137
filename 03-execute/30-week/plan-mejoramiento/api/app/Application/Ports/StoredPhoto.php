<?php

namespace App\Application\Ports;

final readonly class StoredPhoto
{
    public function __construct(public string $bytes, public string $mimeType)
    {
    }
}
