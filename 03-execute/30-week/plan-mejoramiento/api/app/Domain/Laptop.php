<?php

namespace App\Domain;

use App\Domain\Exceptions\LaptopNotAvailable;
use App\Domain\Exceptions\ValidationFailed;
use DateTimeImmutable;
use LogicException;

final class Laptop
{
    private function __construct(
        public readonly string $id,
        private string $name,
        private string $brand,
        public readonly ModelCode $modelCode,
        private Category $category,
        private int $totalUnits,
        private int $availableUnits,
        private ?string $photoKey,
        private ?DateTimeImmutable $deletedAt,
    ) {
    }

    public static function register(string $id, string $name, string $brand, ModelCode $modelCode, Category $category, Quantity $total): self
    {
        return new self($id, self::cleanName($name), self::cleanBrand($brand), $modelCode, $category, $total->value, $total->value, null, null);
    }

    /** Rebuilds a laptop that is already stored; used only by the persistence adapter. */
    public static function restore(
        string $id,
        string $name,
        string $brand,
        string $modelCode,
        Category $category,
        int $totalUnits,
        int $availableUnits,
        ?string $photoKey,
        ?DateTimeImmutable $deletedAt,
    ): self {
        return new self($id, $name, $brand, ModelCode::from($modelCode), $category, $totalUnits, $availableUnits, $photoKey, $deletedAt);
    }

    public function withdraw(): void
    {
        if ($this->availableUnits === 0) {
            throw LaptopNotAvailable::create($this->name);
        }

        $this->availableUnits--;
    }

    public function restock(): void
    {
        if ($this->availableUnits >= $this->totalUnits) {
            throw new LogicException('Available units can never exceed the total.');
        }

        $this->availableUnits++;
    }

    public function update(string $name, string $brand, Category $category, Quantity $total): void
    {
        $onLoan = $this->totalUnits - $this->availableUnits;
        $newAvailable = $total->value - $onLoan;

        if ($newAvailable < 0) {
            throw ValidationFailed::field('total_units', "hay $onLoan unidades prestadas; el total no puede ser menor");
        }

        $this->name = self::cleanName($name);
        $this->brand = self::cleanBrand($brand);
        $this->category = $category;
        $this->totalUnits = $total->value;
        $this->availableUnits = $newAvailable;
    }

    public function retire(DateTimeImmutable $now): void
    {
        $this->deletedAt ??= $now;
    }

    public function attachPhoto(string $photoKey): void
    {
        if (trim($photoKey) === '') {
            throw new LogicException('A photo key can never be empty.');
        }

        $this->photoKey = $photoKey;
    }

    /** Returns the key that was removed, so the caller can delete the file after committing. */
    public function detachPhoto(): ?string
    {
        $previous = $this->photoKey;
        $this->photoKey = null;

        return $previous;
    }

    public function hasUnitsOnLoan(): bool
    {
        return $this->availableUnits < $this->totalUnits;
    }

    public function isRetired(): bool
    {
        return $this->deletedAt !== null;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function brand(): string
    {
        return $this->brand;
    }

    public function category(): Category
    {
        return $this->category;
    }

    public function totalUnits(): int
    {
        return $this->totalUnits;
    }

    public function availableUnits(): int
    {
        return $this->availableUnits;
    }

    public function photoKey(): ?string
    {
        return $this->photoKey;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    private static function cleanName(string $name): string
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 200) {
            throw ValidationFailed::field('name', 'obligatorio, máximo 200 caracteres');
        }

        return $name;
    }

    private static function cleanBrand(string $brand): string
    {
        $brand = trim($brand);
        if ($brand === '' || mb_strlen($brand) > 120) {
            throw ValidationFailed::field('brand', 'obligatoria, máximo 120 caracteres');
        }

        return $brand;
    }
}
