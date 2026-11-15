<?php

namespace Tests\Feature;

use App\Application\Ports\MostRequestedRow;
use App\Domain\Exceptions\InvalidDateRange;
use App\Domain\Exceptions\ValidationFailed;
use App\Domain\Laptop;
use App\Domain\Student;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryApplication;
use Tests\Fakes\InMemoryCategoryRepository;

/**
 * HU-10 and ADR-004, driven through the services: lend, rename, then ask for the report.
 * Runs against the in-memory reader; the PostgreSQL reader joins this test with T-13.
 */
final class MostRequestedReportTest extends TestCase
{
    private const USER = 'assistant-user-id';

    private InMemoryApplication $app;

    private Laptop $thinkPad;

    private Laptop $macBook;

    protected function setUp(): void
    {
        $this->app = new InMemoryApplication('2026-11-03T09:00:00Z');
        $this->thinkPad = $this->app->createLaptop()->create('ThinkPad E14 Gen 5', 'Lenovo', 'TP-E14-G5', InMemoryCategoryRepository::PROGRAMMING, 10);
        $this->macBook = $this->app->createLaptop()->create('MacBook Air M2', 'Apple', 'MBA-M2-13', InMemoryCategoryRepository::DESIGN, 10);
    }

    public function test_report_keeps_the_old_name_after_the_laptop_is_renamed(): void
    {
        $this->lendAndReturn($this->thinkPad, $this->student('1001'));

        $this->app->updateLaptop()->update($this->thinkPad->id, 'ThinkPad E14 (2023)', 'Lenovo', InMemoryCategoryRepository::PROGRAMMING, 10);

        $this->assertSame(
            [[$this->thinkPad->id, 'ThinkPad E14 Gen 5', 'Programación', 1]],
            $this->rows('2026-11-01', '2026-11-30'),
        );
    }

    public function test_renaming_a_category_does_not_change_a_closed_period(): void
    {
        $this->lendAndReturn($this->macBook, $this->student('1001'));

        $this->app->categories->rename(InMemoryCategoryRepository::DESIGN, 'Diseño gráfico');

        $this->assertSame('Diseño', $this->app->mostRequested()->report('2026-11-01', '2026-11-30')->rows[0]->categoryName);
    }

    public function test_a_laptop_that_changed_category_inside_the_range_gets_two_rows(): void
    {
        $this->lendAndReturn($this->thinkPad, $this->student('1001'));
        $this->app->updateLaptop()->update($this->thinkPad->id, 'ThinkPad E14 Gen 5', 'Lenovo', InMemoryCategoryRepository::BASIC, 10);
        $this->lendAndReturn($this->thinkPad, $this->student('1002'));
        $this->lendAndReturn($this->thinkPad, $this->student('1003'));

        $this->assertSame([
            [$this->thinkPad->id, 'ThinkPad E14 Gen 5', 'Básicos', 2],
            [$this->thinkPad->id, 'ThinkPad E14 Gen 5', 'Programación', 1],
        ], $this->rows('2026-11-01', '2026-11-30'));
    }

    public function test_rows_are_ordered_by_times_requested_then_name_and_both_days_are_inclusive(): void
    {
        $this->app->clock->advance('-2 days');
        $this->lendAndReturn($this->macBook, $this->student('0999'));
        $this->app->clock->advance('+2 days');
        $this->lendAndReturn($this->macBook, $this->student('1001'));
        $this->lendAndReturn($this->thinkPad, $this->student('1002'));
        $this->lendAndReturn($this->thinkPad, $this->student('1003'));
        $this->app->clock->advance('+27 days +14 hours');
        $this->lendAndReturn($this->macBook, $this->student('1004'));
        $this->app->clock->advance('+1 day');
        $this->lendAndReturn($this->thinkPad, $this->student('1005'));

        $this->assertSame([
            [$this->macBook->id, 'MacBook Air M2', 'Diseño', 2],
            [$this->thinkPad->id, 'ThinkPad E14 Gen 5', 'Programación', 2],
        ], $this->rows('2026-11-03', '2026-11-30'));
    }

    public function test_report_rows_carry_no_student_or_assistant_data(): void
    {
        $this->lendAndReturn($this->thinkPad, $this->student('1001'));

        $row = $this->app->mostRequested()->report('2026-11-01', '2026-11-30')->rows[0];

        $this->assertSame(['laptopId', 'laptopName', 'categoryName', 'timesRequested'], array_keys(get_object_vars($row)));
    }

    public function test_range_without_loans_is_an_empty_list(): void
    {
        $this->lendAndReturn($this->thinkPad, $this->student('1001'));

        $this->assertSame([], $this->rows('2026-10-01', '2026-10-31'));
    }

    public function test_end_before_start_or_missing_days_are_rejected(): void
    {
        $this->assertThrows(InvalidDateRange::class, fn () => $this->app->mostRequested()->report('2026-11-30', '2026-11-01'));
        $this->assertThrows(ValidationFailed::class, fn () => $this->app->mostRequested()->report(null, '2026-11-01'));
        $this->assertThrows(ValidationFailed::class, fn () => $this->app->mostRequested()->report('2026-11-01', ' '));
        $this->assertThrows(ValidationFailed::class, fn () => $this->app->mostRequested()->report('2026-11-01', '2026-13-01'));
    }

    public function test_same_day_range_is_valid(): void
    {
        $this->lendAndReturn($this->thinkPad, $this->student('1001'));

        $report = $this->app->mostRequested()->report('2026-11-03', '2026-11-03');

        $this->assertSame('2026-11-03', $report->range->from->format('Y-m-d'));
        $this->assertCount(1, $report->rows);
    }

    private function student(string $suffix): Student
    {
        return $this->app->createStudent()->create("Estudiante {$suffix}", "100000{$suffix}");
    }

    private function lendAndReturn(Laptop $laptop, Student $student): void
    {
        $loan = $this->app->lend()->lend($student->id, [$laptop->id], self::USER);
        $this->app->returnItem()->returnItem($loan->id, $loan->items()[0]->id);
    }

    /** @return list<array{0: string, 1: string, 2: string, 3: int}> */
    private function rows(string $from, string $to): array
    {
        return array_map(
            fn (MostRequestedRow $row) => [$row->laptopId, $row->laptopName, $row->categoryName, $row->timesRequested],
            $this->app->mostRequested()->report($from, $to)->rows,
        );
    }

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
