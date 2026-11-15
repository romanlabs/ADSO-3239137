<?php

namespace Tests\Unit\Application;

use App\Application\Ports\PageRequest;
use App\Application\Services\CreateLaptopService;
use App\Application\Services\LaptopPhotoService;
use App\Application\Services\ListCategoriesService;
use App\Application\Services\RetireLaptopService;
use App\Application\Services\SearchLaptopsService;
use App\Application\Services\UpdateLaptopService;
use App\Domain\Category;
use App\Domain\Exceptions\LaptopHasOpenLoans;
use App\Domain\Exceptions\LaptopNotAvailable;
use App\Domain\Exceptions\ModelCodeTaken;
use App\Domain\Exceptions\NotFound;
use App\Domain\Exceptions\ValidationFailed;
use App\Domain\Laptop;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FixedClock;
use Tests\Fakes\InMemoryCategoryRepository;
use Tests\Fakes\InMemoryLaptopRepository;
use Tests\Fakes\InMemoryPhotoStorage;
use Tests\Fakes\SequentialIdGenerator;

final class LaptopServicesTest extends TestCase
{
    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private InMemoryLaptopRepository $laptops;

    private InMemoryCategoryRepository $categories;

    private InMemoryPhotoStorage $photos;

    private CreateLaptopService $create;

    private UpdateLaptopService $update;

    private RetireLaptopService $retire;

    private SearchLaptopsService $search;

    private LaptopPhotoService $photo;

    protected function setUp(): void
    {
        $this->laptops = new InMemoryLaptopRepository;
        $this->categories = new InMemoryCategoryRepository;
        $this->photos = new InMemoryPhotoStorage;
        $this->create = new CreateLaptopService($this->laptops, $this->categories, new SequentialIdGenerator);
        $this->update = new UpdateLaptopService($this->laptops, $this->categories);
        $this->retire = new RetireLaptopService($this->laptops, new FixedClock);
        $this->search = new SearchLaptopsService($this->laptops);
        $this->photo = new LaptopPhotoService($this->laptops, $this->photos);
    }

    public function test_new_laptop_starts_with_all_units_available_and_upper_case_model_code(): void
    {
        $laptop = $this->thinkPad();

        $stored = $this->search->get($laptop->id);
        $this->assertSame('TP-E14-G5', $stored->modelCode->value);
        $this->assertSame(4, $stored->totalUnits());
        $this->assertSame(4, $stored->availableUnits());
        $this->assertSame('Programación', $stored->category()->name);
        $this->assertNull($stored->photoKey());
    }

    public function test_model_code_is_unique_among_active_laptops_only(): void
    {
        $first = $this->thinkPad();

        try {
            $this->create->create('Otro', 'Lenovo', 'TP-E14-G5', InMemoryCategoryRepository::BASIC, 1);
            $this->fail('A repeated model code must be rejected.');
        } catch (ModelCodeTaken) {
        }

        $this->retire->retire($first->id);
        $again = $this->create->create('ThinkPad E14 Gen 5 (reposición)', 'Lenovo', 'tp-e14-g5', InMemoryCategoryRepository::PROGRAMMING, 2);

        $this->assertSame('TP-E14-G5', $again->modelCode->value);
    }

    public function test_invalid_input_and_unknown_category_are_rejected(): void
    {
        $this->assertThrows(ValidationFailed::class, fn () => $this->create->create('X', 'Y', 'abc', InMemoryCategoryRepository::BASIC, 1));
        $this->assertThrows(ValidationFailed::class, fn () => $this->create->create('X', 'Y', 'ABCD', InMemoryCategoryRepository::BASIC, 0));
        $this->assertThrows(ValidationFailed::class, fn () => $this->create->create('  ', 'Y', 'ABCD', InMemoryCategoryRepository::BASIC, 1));
        $this->assertThrows(NotFound::class, fn () => $this->create->create('X', 'Y', 'ABCD', 'missing-category', 1));
    }

    public function test_changing_the_total_moves_available_units_by_the_same_difference(): void
    {
        $laptop = $this->thinkPad();
        $this->lendOneUnit($laptop->id);

        $updated = $this->update->update($laptop->id, 'ThinkPad E14', 'Lenovo', InMemoryCategoryRepository::BASIC, 6);

        $this->assertSame(6, $updated->totalUnits());
        $this->assertSame(5, $updated->availableUnits());
        $this->assertSame('Básicos', $this->search->get($laptop->id)->category()->name);
    }

    public function test_total_below_units_on_loan_is_rejected_and_nothing_changes(): void
    {
        $laptop = $this->create->create('iPad 10', 'Apple', 'IPAD-10', InMemoryCategoryRepository::TABLETS, 2);
        $this->lendOneUnit($laptop->id);
        $this->lendOneUnit($laptop->id);

        $this->assertThrows(ValidationFailed::class, fn () => $this->update->update($laptop->id, 'iPad 10', 'Apple', InMemoryCategoryRepository::TABLETS, 1));
        $this->assertSame(2, $this->laptops->stored($laptop->id)->totalUnits());
        $this->assertSame(0, $this->laptops->stored($laptop->id)->availableUnits());
    }

    public function test_update_loses_against_a_concurrent_loan_instead_of_overwriting_units(): void
    {
        $laptop = $this->thinkPad();
        $this->laptops->anotherOperatorTakesAUnitAfterNextRead($laptop->id);

        $this->assertThrows(LaptopNotAvailable::class, fn () => $this->update->update($laptop->id, 'ThinkPad', 'Lenovo', InMemoryCategoryRepository::PROGRAMMING, 4));
        $this->assertSame(3, $this->laptops->stored($laptop->id)->availableUnits());
    }

    public function test_retired_laptop_disappears_from_search_and_detail(): void
    {
        $laptop = $this->thinkPad();

        $this->retire->retire($laptop->id);

        $this->assertSame(0, $this->search->search(null, null, PageRequest::of())->total);
        $this->assertThrows(NotFound::class, fn () => $this->search->get($laptop->id));
        $this->assertThrows(NotFound::class, fn () => $this->retire->retire($laptop->id));
        $this->assertTrue($this->laptops->findIncludingRetired($laptop->id)->isRetired());
    }

    public function test_laptop_with_units_on_loan_cannot_be_retired(): void
    {
        $laptop = $this->thinkPad();
        $this->lendOneUnit($laptop->id);

        $this->assertThrows(LaptopHasOpenLoans::class, fn () => $this->retire->retire($laptop->id));
        $this->assertFalse($this->laptops->stored($laptop->id)->isRetired());
    }

    public function test_search_by_partial_text_and_category_ordered_by_name_and_paginated(): void
    {
        $this->create->create('MacBook Air M2', 'Apple', 'MBA-M2-13', InMemoryCategoryRepository::DESIGN, 3);
        $this->create->create('iPad Air', 'Apple', 'IPAD-AIR-5', InMemoryCategoryRepository::TABLETS, 2);
        $this->create->create('Chromebook 14', 'Acer', 'CB-14-01', InMemoryCategoryRepository::BASIC, 5);
        $this->thinkPad();

        $apple = $this->search->search('  apple ', null, PageRequest::of(1, 1));
        $this->assertSame(2, $apple->total);
        $this->assertSame(['iPad Air'], array_map(fn (Laptop $l) => $l->name(), $apple->items));

        $secondPage = $this->search->search('APPLE', null, PageRequest::of(2, 1));
        $this->assertSame(['MacBook Air M2'], array_map(fn (Laptop $l) => $l->name(), $secondPage->items));

        $tablets = $this->search->search('', InMemoryCategoryRepository::TABLETS, PageRequest::of());
        $this->assertSame(1, $tablets->total);

        $all = $this->search->search(null, null, PageRequest::of());
        $this->assertSame(['Chromebook 14', 'iPad Air', 'MacBook Air M2', 'ThinkPad E14 Gen 5'], array_map(fn (Laptop $l) => $l->name(), $all->items));
    }

    public function test_page_size_is_capped_at_fifty(): void
    {
        $this->assertSame(50, PageRequest::of(1, 50)->size);
        $this->assertThrows(ValidationFailed::class, fn () => PageRequest::of(1, 51));
        $this->assertThrows(ValidationFailed::class, fn () => PageRequest::of(0, 20));
    }

    public function test_categories_are_the_five_fixed_ones_ordered_by_name(): void
    {
        $names = array_map(fn (Category $c) => $c->name, (new ListCategoriesService($this->categories))->list());

        $this->assertSame(['Accesorios', 'Básicos', 'Diseño', 'Programación', 'Tabletas'], $names);
    }

    public function test_png_photo_is_stored_under_an_opaque_key_and_can_be_read_back(): void
    {
        $laptop = $this->thinkPad();

        $updated = $this->photo->attach($laptop->id, base64_decode(self::PNG_1X1));

        $this->assertNotNull($updated->photoKey());
        $this->assertSame($updated->photoKey(), $this->laptops->stored($laptop->id)->photoKey());
        $this->assertSame('image/png', $this->photo->read($laptop->id)->mimeType);
    }

    public function test_replacing_and_removing_a_photo_deletes_the_old_file(): void
    {
        $laptop = $this->thinkPad();
        $first = $this->photo->attach($laptop->id, base64_decode(self::PNG_1X1))->photoKey();
        $second = $this->photo->attach($laptop->id, base64_decode(self::PNG_1X1))->photoKey();

        $this->assertSame([$second], $this->photos->keys());
        $this->assertNotSame($first, $second);

        $this->photo->detach($laptop->id);

        $this->assertSame([], $this->photos->keys());
        $this->assertNull($this->laptops->stored($laptop->id)->photoKey());
        $this->assertThrows(NotFound::class, fn () => $this->photo->read($laptop->id));
    }

    public function test_photo_over_two_megabytes_or_not_jpg_png_is_rejected(): void
    {
        $laptop = $this->thinkPad();
        $tooBig = base64_decode(self::PNG_1X1).str_repeat("\0", 3 * 1024 * 1024);

        $this->assertThrows(ValidationFailed::class, fn () => $this->photo->attach($laptop->id, $tooBig));
        $this->assertThrows(ValidationFailed::class, fn () => $this->photo->attach($laptop->id, "GIF89a\x01\x00\x01\x00\x00\x00\x00;"));
        $this->assertThrows(ValidationFailed::class, fn () => $this->photo->attach($laptop->id, 'not an image, just text'));
        $this->assertSame([], $this->photos->keys());
        $this->assertNull($this->laptops->stored($laptop->id)->photoKey());
    }

    private function thinkPad(): Laptop
    {
        return $this->create->create(' ThinkPad E14 Gen 5 ', 'Lenovo', ' tp-e14-g5 ', InMemoryCategoryRepository::PROGRAMMING, 4);
    }

    private function lendOneUnit(string $laptopId): void
    {
        $laptop = $this->laptops->find($laptopId);
        $laptop->withdraw();
        $this->laptops->save($laptop);
    }

    /** @param class-string<\Throwable> $expected */
    private function assertThrows(string $expected, callable $action): void
    {
        try {
            $action();
        } catch (\Throwable $error) {
            $this->assertInstanceOf($expected, $error);

            return;
        }

        $this->fail("Expected {$expected} to be thrown.");
    }
}
