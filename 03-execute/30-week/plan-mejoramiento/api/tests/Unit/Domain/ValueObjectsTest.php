<?php

namespace Tests\Unit\Domain;

use App\Domain\DateRange;
use App\Domain\DocumentNumber;
use App\Domain\Exceptions\InvalidDateRange;
use App\Domain\Exceptions\ValidationFailed;
use App\Domain\ModelCode;
use App\Domain\Quantity;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValueObjectsTest extends TestCase
{
    public function test_model_code_is_trimmed_and_uppercased(): void
    {
        $this->assertSame('TP-E14-G5', ModelCode::from('  tp-e14-g5 ')->value);
    }

    #[DataProvider('invalidModelCodes')]
    public function test_model_code_rejects_invalid_values(string $raw): void
    {
        $this->expectException(ValidationFailed::class);
        ModelCode::from($raw);
    }

    public static function invalidModelCodes(): array
    {
        return [
            'too short' => ['AB1'],
            'too long' => [str_repeat('A', 21)],
            'spaces inside' => ['TP E14'],
            'symbols' => ['TP_E14#'],
            'empty' => ['   '],
        ];
    }

    public function test_quantity_must_be_at_least_one(): void
    {
        $this->assertSame(1, Quantity::from(1)->value);

        $this->expectException(ValidationFailed::class);
        Quantity::from(0);
    }

    public function test_document_number_accepts_letters_and_digits_only(): void
    {
        $this->assertSame('1001234567', DocumentNumber::from(' 1001234567 ')->value);

        $this->expectException(ValidationFailed::class);
        DocumentNumber::from('10.012.345');
    }

    public function test_date_range_includes_the_whole_last_day(): void
    {
        $range = DateRange::fromDays(new DateTimeImmutable('2026-11-01'), new DateTimeImmutable('2026-11-30'));

        $this->assertSame('2026-11-01T00:00:00+00:00', $range->startsAt()->format(DATE_ATOM));
        $this->assertSame('2026-12-01T00:00:00+00:00', $range->endsBefore()->format(DATE_ATOM));
    }

    public function test_date_range_with_end_before_start_is_rejected(): void
    {
        $this->expectException(InvalidDateRange::class);
        DateRange::fromDays(new DateTimeImmutable('2026-11-30'), new DateTimeImmutable('2026-11-01'));
    }

    public function test_same_day_range_is_valid(): void
    {
        $range = DateRange::fromDays(new DateTimeImmutable('2026-11-16'), new DateTimeImmutable('2026-11-16'));

        $this->assertEquals($range->from, $range->to);
    }
}
