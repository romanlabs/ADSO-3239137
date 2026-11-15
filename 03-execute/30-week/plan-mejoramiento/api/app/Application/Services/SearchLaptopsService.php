<?php

namespace App\Application\Services;

use App\Application\Ports\LaptopRepository;
use App\Application\Ports\Page;
use App\Application\Ports\PageRequest;
use App\Domain\Exceptions\NotFound;
use App\Domain\Laptop;

/** HU-04: search active laptops and read one by id. */
final readonly class SearchLaptopsService
{
    public function __construct(private LaptopRepository $laptops)
    {
    }

    /** @return Page<Laptop> */
    public function search(?string $text, ?string $categoryId, PageRequest $page): Page
    {
        $text = $text === null ? null : trim($text);

        return $this->laptops->search($text === '' ? null : $text, $categoryId === '' ? null : $categoryId, $page);
    }

    public function get(string $id): Laptop
    {
        return $this->laptops->find($id) ?? throw NotFound::resource('el portátil');
    }
}
