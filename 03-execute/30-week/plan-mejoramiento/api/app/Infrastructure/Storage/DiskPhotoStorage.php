<?php

namespace App\Infrastructure\Storage;

use App\Application\Ports\PhotoStorage;
use App\Application\Ports\StoredPhoto;
use finfo;
use RuntimeException;

/**
 * Photos as plain files in one directory (plan D-08). The key is 32 random hex characters and never a path, so a key
 * coming from outside can never point to another file. The type is read back from the content, never from a name.
 */
final readonly class DiskPhotoStorage implements PhotoStorage
{
    private const KEY_PATTERN = '/^[0-9a-f]{32}$/';

    public function __construct(private string $directory)
    {
    }

    public function put(string $bytes, string $mimeType): string
    {
        $this->ensureDirectory();
        $key = bin2hex(random_bytes(16));

        if (file_put_contents($this->path($key), $bytes, LOCK_EX) !== strlen($bytes)) {
            throw new RuntimeException('The photo could not be written to disk.');
        }

        return $key;
    }

    public function get(string $key): ?StoredPhoto
    {
        if (! self::isKey($key) || ! is_file($this->path($key))) {
            return null;
        }

        $bytes = (string) file_get_contents($this->path($key));

        return new StoredPhoto($bytes, (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes) ?: 'application/octet-stream');
    }

    public function delete(string $key): void
    {
        if (self::isKey($key) && is_file($this->path($key))) {
            unlink($this->path($key));
        }
    }

    private static function isKey(string $key): bool
    {
        return preg_match(self::KEY_PATTERN, $key) === 1;
    }

    private function path(string $key): string
    {
        return rtrim($this->directory, '/\\').DIRECTORY_SEPARATOR.$key;
    }

    private function ensureDirectory(): void
    {
        if (! is_dir($this->directory) && ! mkdir($this->directory, 0775, true) && ! is_dir($this->directory)) {
            throw new RuntimeException('The photos directory could not be created.');
        }
    }
}
