<?php

namespace App\Application\Services;

use App\Application\Ports\CategoryRepository;
use App\Application\Ports\LaptopRepository;
use App\Domain\Exceptions\NotFound;
use App\Domain\Laptop;
use App\Domain\Quantity;

/** CA-03.3: changing the total moves the available units by the same difference, never below zero. */
final readonly class UpdateLaptopService
{
    public function __construct(
        private LaptopRepository $laptops,
        private CategoryRepository $categories,
    ) {
    }

    public function update(string $id, string $name, string $brand, string $categoryId, int $totalUnits): Laptop
    {
        $laptop = $this->laptops->find($id) ?? throw NotFound::resource('el portátil');
        $total = Quantity::from($totalUnits);
        $category = $this->categories->find($categoryId) ?? throw NotFound::resource('la categoría');

        $laptop->update($name, $brand, $category, $total);
        $this->laptops->save($laptop);

        return $laptop;
    }
}
