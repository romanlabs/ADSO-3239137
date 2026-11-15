<?php

namespace App\Application\Services;

use App\Application\Ports\CategoryRepository;
use App\Application\Ports\IdGenerator;
use App\Application\Ports\LaptopRepository;
use App\Domain\Exceptions\ModelCodeTaken;
use App\Domain\Exceptions\NotFound;
use App\Domain\Laptop;
use App\Domain\ModelCode;
use App\Domain\Quantity;

/** CA-03.1 and CA-03.2: available units start equal to the total. */
final readonly class CreateLaptopService
{
    public function __construct(
        private LaptopRepository $laptops,
        private CategoryRepository $categories,
        private IdGenerator $ids,
    ) {
    }

    public function create(string $name, string $brand, string $modelCode, string $categoryId, int $totalUnits): Laptop
    {
        $code = ModelCode::from($modelCode);
        $total = Quantity::from($totalUnits);
        $category = $this->categories->find($categoryId) ?? throw NotFound::resource('la categoría');

        if ($this->laptops->modelCodeInUse($code->value)) {
            throw ModelCodeTaken::create($code->value);
        }

        $laptop = Laptop::register($this->ids->next(), $name, $brand, $code, $category, $total);
        $this->laptops->add($laptop);

        return $laptop;
    }
}
