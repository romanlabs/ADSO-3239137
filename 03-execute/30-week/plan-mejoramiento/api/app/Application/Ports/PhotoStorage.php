<?php

namespace App\Application\Ports;

interface PhotoStorage
{
    /** Stores the bytes and returns a new opaque key (CA-03.4). */
    public function put(string $bytes, string $mimeType): string;

    /** Returns null when the key has no file. */
    public function get(string $key): ?StoredPhoto;

    public function delete(string $key): void;
}
