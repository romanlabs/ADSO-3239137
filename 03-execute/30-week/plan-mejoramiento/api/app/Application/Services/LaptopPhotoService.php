<?php

namespace App\Application\Services;

use App\Application\Ports\LaptopRepository;
use App\Application\Ports\PhotoStorage;
use App\Application\Ports\StoredPhoto;
use App\Domain\Exceptions\NotFound;
use App\Domain\Exceptions\ValidationFailed;
use App\Domain\Laptop;
use finfo;
use Throwable;

/**
 * CA-03.4: JPG or PNG up to 2 MB, detected by content and not by extension. The laptop keeps only an
 * opaque key; the file that is replaced or removed is deleted after the laptop is saved.
 */
final readonly class LaptopPhotoService
{
    public const MAX_BYTES = 2 * 1024 * 1024;

    public const ALLOWED_TYPES = ['image/jpeg', 'image/png'];

    public function __construct(
        private LaptopRepository $laptops,
        private PhotoStorage $storage,
    ) {
    }

    public function attach(string $laptopId, string $bytes): Laptop
    {
        $laptop = $this->laptops->find($laptopId) ?? throw NotFound::resource('el portátil');

        if ($bytes === '' || strlen($bytes) > self::MAX_BYTES) {
            throw ValidationFailed::field('file', 'obligatorio, máximo 2 MB');
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        if (! in_array($mimeType, self::ALLOWED_TYPES, true)) {
            throw ValidationFailed::field('file', 'solo se aceptan imágenes JPG o PNG');
        }

        $previousKey = $laptop->photoKey();
        $newKey = $this->storage->put($bytes, $mimeType);

        try {
            $laptop->attachPhoto($newKey);
            $this->laptops->save($laptop);
        } catch (Throwable $error) {
            $this->storage->delete($newKey);
            throw $error;
        }

        if ($previousKey !== null) {
            $this->storage->delete($previousKey);
        }

        return $laptop;
    }

    public function detach(string $laptopId): void
    {
        $laptop = $this->laptops->find($laptopId) ?? throw NotFound::resource('el portátil');

        $previousKey = $laptop->detachPhoto();
        $this->laptops->save($laptop);

        if ($previousKey !== null) {
            $this->storage->delete($previousKey);
        }
    }

    public function read(string $laptopId): StoredPhoto
    {
        $laptop = $this->laptops->find($laptopId) ?? throw NotFound::resource('el portátil');
        $key = $laptop->photoKey() ?? throw NotFound::resource('la foto del portátil');

        return $this->storage->get($key) ?? throw NotFound::resource('la foto del portátil');
    }
}
