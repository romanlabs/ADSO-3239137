<?php

namespace Tests\Fakes;

use App\Application\Ports\PhotoStorage;
use App\Application\Ports\StoredPhoto;

final class InMemoryPhotoStorage implements PhotoStorage
{
    /** @var array<string, StoredPhoto> */
    private array $files = [];

    private int $counter = 0;

    public function put(string $bytes, string $mimeType): string
    {
        $key = 'photo-'.(++$this->counter);
        $this->files[$key] = new StoredPhoto($bytes, $mimeType);

        return $key;
    }

    public function get(string $key): ?StoredPhoto
    {
        return $this->files[$key] ?? null;
    }

    public function delete(string $key): void
    {
        unset($this->files[$key]);
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->files);
    }
}
