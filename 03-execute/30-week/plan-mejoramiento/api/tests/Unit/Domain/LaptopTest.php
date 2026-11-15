<?php

namespace Tests\Unit\Domain;

use App\Domain\Category;
use App\Domain\Exceptions\LaptopNotAvailable;
use App\Domain\Exceptions\ValidationFailed;
use App\Domain\Laptop;
use App\Domain\ModelCode;
use App\Domain\Quantity;
use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;

final class LaptopTest extends TestCase
{
    private function laptop(int $total = 2): Laptop
    {
        return Laptop::register(
            'b5f0c1e2-0000-4000-8000-000000000001',
            '  ThinkPad E14 Gen 5 ',
            'Lenovo',
            ModelCode::from('tp-e14-g5'),
            new Category('33333333-3333-4333-8333-333333333333', 'Programación'),
            Quantity::from($total),
        );
    }

    public function test_new_laptop_starts_with_all_units_available_and_no_photo(): void
    {
        $laptop = $this->laptop(4);

        $this->assertSame('ThinkPad E14 Gen 5', $laptop->name());
        $this->assertSame('TP-E14-G5', $laptop->modelCode->value);
        $this->assertSame(4, $laptop->totalUnits());
        $this->assertSame(4, $laptop->availableUnits());
        $this->assertNull($laptop->photoKey());
    }

    public function test_withdraw_never_goes_below_zero(): void
    {
        $laptop = $this->laptop(1);
        $laptop->withdraw();
        $this->assertSame(0, $laptop->availableUnits());

        $this->expectException(LaptopNotAvailable::class);
        $laptop->withdraw();
    }

    public function test_restock_never_goes_above_total(): void
    {
        $laptop = $this->laptop(1);
        $laptop->withdraw();
        $laptop->restock();
        $this->assertSame(1, $laptop->availableUnits());

        $this->expectException(LogicException::class);
        $laptop->restock();
    }

    public function test_changing_total_moves_available_by_the_same_difference(): void
    {
        $laptop = $this->laptop(3);
        $laptop->withdraw();

        $laptop->update('ThinkPad E14', 'Lenovo', $laptop->category(), Quantity::from(5));

        $this->assertSame(5, $laptop->totalUnits());
        $this->assertSame(4, $laptop->availableUnits());
    }

    public function test_total_cannot_drop_below_units_on_loan(): void
    {
        $laptop = $this->laptop(2);
        $laptop->withdraw();
        $laptop->withdraw();

        $this->expectException(ValidationFailed::class);
        $laptop->update('ThinkPad E14', 'Lenovo', $laptop->category(), Quantity::from(1));
    }

    public function test_retire_is_logical_and_keeps_the_first_instant(): void
    {
        $laptop = $this->laptop();
        $first = new DateTimeImmutable('2026-11-16T10:00:00Z');

        $laptop->retire($first);
        $laptop->retire(new DateTimeImmutable('2026-11-17T10:00:00Z'));

        $this->assertTrue($laptop->isRetired());
        $this->assertEquals($first, $laptop->deletedAt());
    }

    public function test_photo_key_is_null_or_non_empty(): void
    {
        $laptop = $this->laptop();
        $laptop->attachPhoto('photos/abc.png');
        $this->assertSame('photos/abc.png', $laptop->detachPhoto());
        $this->assertNull($laptop->photoKey());

        $this->expectException(LogicException::class);
        $laptop->attachPhoto('');
    }

    public function test_name_is_required(): void
    {
        $this->expectException(ValidationFailed::class);

        Laptop::register('id', '   ', 'Lenovo', ModelCode::from('TP-E14'), new Category('c', 'Básicos'), Quantity::from(1));
    }
}
