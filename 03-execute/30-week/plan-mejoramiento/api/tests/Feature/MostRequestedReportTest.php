<?php

namespace Tests\Feature;

use App\Application\Ports\MostRequestedRow;
use App\Domain\Exceptions\InvalidDateRange;
use App\Domain\Exceptions\ValidationFailed;
use App\Domain\Laptop;
use App\Domain\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Fakes\InMemoryApplication;
use Tests\Fakes\InMemoryCategoryRepository;
use Tests\Support\PostgresApplication;
use Tests\TestCase;

/**
 * HU-10 and ADR-004, driven through the services: lend, rename, then ask for the report.
 * Every scenario runs twice: against the in-memory fakes and against PostgreSQL with the real adapters (T-13).
 */
final class MostRequestedReportTest extends TestCase
{
    use RefreshDatabase;

    private InMemoryApplication|PostgresApplication $system;

    private Laptop $thinkPad;

    private Laptop $macBook;

    /** @return array<string, array{0: string}> */
    public static function backends(): array
    {
        return ['in memory' => ['memory'], 'postgresql' => ['postgresql']];
    }

    private function startOn(string $backend): void
    {
        $this->system = $backend === 'memory'
            ? new InMemoryApplication('2026-11-03T09:00:00Z')
            : new PostgresApplication($this->app, '2026-11-03T09:00:00Z');
        $this->thinkPad = $this->system->createLaptop()->create('ThinkPad E14 Gen 5', 'Lenovo', 'TP-E14-G5', InMemoryCategoryRepository::PROGRAMMING, 10);
        $this->macBook = $this->system->createLaptop()->create('MacBook Air M2', 'Apple', 'MBA-M2-13', InMemoryCategoryRepository::DESIGN, 10);
    }

    #[DataProvider('backends')]
    public function test_report_keeps_the_old_name_after_the_laptop_is_renamed(string $backend): void
    {
        $this->startOn($backend);
        $this->lendAndReturn($this->thinkPad, $this->student('1001'));

        $this->system->updateLaptop()->update($this->thinkPad->id, 'ThinkPad E14 (2023)', 'Lenovo', InMemoryCategoryRepository::PROGRAMMING, 10);

        $this->assertSame(
            [[$this->thinkPad->id, 'ThinkPad E14 Gen 5', 'Programación', 1]],
            $this->rows('2026-11-01', '2026-11-30'),
        );
    }

    #[DataProvider('backends')]
    public function test_renaming_a_category_does_not_change_a_closed_period(string $backend): void
    {
        $this->startOn($backend);
        $this->lendAndReturn($this->macBook, $this->student('1001'));

        $this->system->renameCategory(InMemoryCategoryRepository::DESIGN, 'Diseño gráfico');

        $this->assertSame('Diseño', $this->system->mostRequested()->report('2026-11-01', '2026-11-30')->rows[0]->categoryName);
    }

    #[DataProvider('backends')]
    public function test_a_laptop_that_changed_category_inside_the_range_gets_two_rows(string $backend): void
    {
        $this->startOn($backend);
        $this->lendAndReturn($this->thinkPad, $this->student('1001'));
        $this->system->updateLaptop()->update($this->thinkPad->id, 'ThinkPad E14 Gen 5', 'Lenovo', InMemoryCategoryRepository::BASIC, 10);
        $this->lendAndReturn($this->thinkPad, $this->student('1002'));
        $this->lendAndReturn($this->thinkPad, $this->student('1003'));

        $this->assertSame([
            [$this->thinkPad->id, 'ThinkPad E14 Gen 5', 'Básicos', 2],
            [$this->thinkPad->id, 'ThinkPad E14 Gen 5', 'Programación', 1],
        ], $this->rows('2026-11-01', '2026-11-30'));
    }

    #[DataProvider('backends')]
    public function test_rows_are_ordered_by_times_requested_then_name_and_both_days_are_inclusive(string $backend): void
    {
        $this->startOn($backend);
        $this->system->clock->advance('-2 days');
        $this->lendAndReturn($this->macBook, $this->student('0999'));
        $this->system->clock->advance('+2 days');
        $this->lendAndReturn($this->macBook, $this->student('1001'));
        $this->lendAndReturn($this->thinkPad, $this->student('1002'));
        $this->lendAndReturn($this->thinkPad, $this->student('1003'));
        $this->system->clock->advance('+27 days +14 hours');
        $this->lendAndReturn($this->macBook, $this->student('1004'));
        $this->system->clock->advance('+1 day');
        $this->lendAndReturn($this->thinkPad, $this->student('1005'));

        $this->assertSame([
            [$this->macBook->id, 'MacBook Air M2', 'Diseño', 2],
            [$this->thinkPad->id, 'ThinkPad E14 Gen 5', 'Programación', 2],
        ], $this->rows('2026-11-03', '2026-11-30'));
    }

    #[DataProvider('backends')]
    public function test_report_rows_carry_no_student_or_assistant_data(string $backend): void
    {
        $this->startOn($backend);
        $this->lendAndReturn($this->thinkPad, $this->student('1001'));

        $row = $this->system->mostRequested()->report('2026-11-01', '2026-11-30')->rows[0];

        $this->assertSame(['laptopId', 'laptopName', 'categoryName', 'timesRequested'], array_keys(get_object_vars($row)));
    }

    #[DataProvider('backends')]
    public function test_range_without_loans_is_an_empty_list(string $backend): void
    {
        $this->startOn($backend);
        $this->lendAndReturn($this->thinkPad, $this->student('1001'));

        $this->assertSame([], $this->rows('2026-10-01', '2026-10-31'));
    }

    #[DataProvider('backends')]
    public function test_end_before_start_or_missing_days_are_rejected(string $backend): void
    {
        $this->startOn($backend);
        $this->assertFailsWith(InvalidDateRange::class, fn () => $this->system->mostRequested()->report('2026-11-30', '2026-11-01'));
        $this->assertFailsWith(ValidationFailed::class, fn () => $this->system->mostRequested()->report(null, '2026-11-01'));
        $this->assertFailsWith(ValidationFailed::class, fn () => $this->system->mostRequested()->report('2026-11-01', ' '));
        $this->assertFailsWith(ValidationFailed::class, fn () => $this->system->mostRequested()->report('2026-11-01', '2026-13-01'));
    }

    #[DataProvider('backends')]
    public function test_same_day_range_is_valid(string $backend): void
    {
        $this->startOn($backend);
        $this->lendAndReturn($this->thinkPad, $this->student('1001'));

        $report = $this->system->mostRequested()->report('2026-11-03', '2026-11-03');

        $this->assertSame('2026-11-03', $report->range->from->format('Y-m-d'));
        $this->assertCount(1, $report->rows);
    }

    private function student(string $suffix): Student
    {
        return $this->system->createStudent()->create("Estudiante {$suffix}", "100000{$suffix}");
    }

    private function lendAndReturn(Laptop $laptop, Student $student): void
    {
        $loan = $this->system->lend()->lend($student->id, [$laptop->id], $this->system->operatorId);
        $this->system->returnItem()->returnItem($loan->id, $loan->items()[0]->id);
    }

    /** @return list<array{0: string, 1: string, 2: string, 3: int}> */
    private function rows(string $from, string $to): array
    {
        return array_map(
            fn (MostRequestedRow $row) => [$row->laptopId, $row->laptopName, $row->categoryName, $row->timesRequested],
            $this->system->mostRequested()->report($from, $to)->rows,
        );
    }

    private function assertFailsWith(string $expected, callable $action): void
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
