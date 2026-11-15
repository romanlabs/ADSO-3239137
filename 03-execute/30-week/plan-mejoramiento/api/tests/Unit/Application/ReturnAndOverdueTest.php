<?php

namespace Tests\Unit\Application;

use App\Application\Ports\PageRequest;
use App\Application\Services\LoanView;
use App\Application\Services\OverdueLine;
use App\Domain\Exceptions\InvalidDateRange;
use App\Domain\Exceptions\ItemAlreadyReturned;
use App\Domain\Exceptions\NotFound;
use App\Domain\Exceptions\StudentHasOverdue;
use App\Domain\Exceptions\ValidationFailed;
use App\Domain\Laptop;
use App\Domain\Loan;
use App\Domain\LoanStatus;
use App\Domain\Student;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryApplication;
use Tests\Fakes\InMemoryCategoryRepository;

final class ReturnAndOverdueTest extends TestCase
{
    private const USER = 'assistant-user-id';

    private InMemoryApplication $app;

    private Laptop $thinkPad;

    protected function setUp(): void
    {
        $this->app = new InMemoryApplication('2026-11-01T10:00:00Z');
        $this->thinkPad = $this->app->createLaptop()->create('ThinkPad E14 Gen 5', 'Lenovo', 'TP-E14-G5', InMemoryCategoryRepository::PROGRAMMING, 5);
    }

    public function test_returning_an_item_sets_the_instant_adds_one_unit_and_closes_the_loan(): void
    {
        $loan = $this->lendTo($this->student('Laura Gómez', '1001234567'));
        $this->app->clock->advance('+3 days');

        $item = $this->app->returnItem()->returnItem($loan->id, $loan->items()[0]->id);

        $this->assertSame('2026-11-04T10:00:00+00:00', $item->returnedAt()->format(DATE_ATOM));
        $this->assertSame(5, $this->app->laptops->stored($this->thinkPad->id)->availableUnits());
        $this->assertSame(LoanStatus::Closed, $this->app->listLoans()->get($loan->id)->status);
    }

    public function test_returning_the_same_item_twice_is_a_conflict_and_never_adds_two_units(): void
    {
        $loan = $this->lendTo($this->student('Laura Gómez', '1001234567'));
        $itemId = $loan->items()[0]->id;
        $this->app->returnItem()->returnItem($loan->id, $itemId);

        $this->assertThrows(ItemAlreadyReturned::class, fn () => $this->app->returnItem()->returnItem($loan->id, $itemId));
        $this->assertSame(5, $this->app->laptops->stored($this->thinkPad->id)->availableUnits());
    }

    public function test_two_operators_returning_at_once_add_only_one_unit(): void
    {
        $loan = $this->lendTo($this->student('Laura Gómez', '1001234567'));
        $itemId = $loan->items()[0]->id;
        $staleLoan = $this->app->loans->find($loan->id);
        $staleLaptop = $this->app->laptops->findIncludingRetired($this->thinkPad->id);

        $this->app->returnItem()->returnItem($loan->id, $itemId);
        $staleLoan->returnItem($itemId, $staleLaptop, $this->app->clock->now());

        $this->assertThrows(ItemAlreadyReturned::class, fn () => $this->app->loans->saveReturn($staleLoan, $itemId, $staleLaptop));
        $this->assertSame(5, $this->app->laptops->stored($this->thinkPad->id)->availableUnits());
    }

    public function test_unknown_loan_or_item_is_not_found(): void
    {
        $loan = $this->lendTo($this->student('Laura Gómez', '1001234567'));

        $this->assertThrows(NotFound::class, fn () => $this->app->returnItem()->returnItem('missing-loan', $loan->items()[0]->id));
        $this->assertThrows(NotFound::class, fn () => $this->app->returnItem()->returnItem($loan->id, 'missing-item'));
    }

    public function test_overdue_lists_only_unreturned_items_past_due_with_most_days_late_first(): void
    {
        $laura = $this->student('Laura Gómez', '1001234567');
        $pedro = $this->student('Pedro Ruiz', '1002223334');
        $sofia = $this->student('Sofía Díaz', '1003334445');
        $tomas = $this->student('Tomás León', '1004445556');

        $this->lendTo($laura);
        $this->app->clock->advance('+2 days');
        $pedrosLoan = $this->lendTo($pedro);
        $this->app->clock->advance('+3 days');
        $this->lendTo($sofia);
        $this->app->clock->advance('+12 days');
        $this->lendTo($tomas);
        $this->app->clock->advance('+4 days');

        $this->app->returnItem()->returnItem($pedrosLoan->id, $pedrosLoan->items()[0]->id);
        $lines = $this->app->overdue()->list();

        $this->assertSame(['Laura Gómez', 'Sofía Díaz'], array_map(fn (OverdueLine $line) => $line->studentName, $lines));
        $this->assertSame([6, 1], array_map(fn (OverdueLine $line) => $line->daysLate, $lines));
        $this->assertSame('ThinkPad E14 Gen 5', $lines[0]->laptopName);
        $this->assertSame('2026-11-16T10:00:00+00:00', $lines[0]->dueAt->format(DATE_ATOM));
    }

    public function test_a_student_with_an_overdue_item_can_borrow_again_after_returning_it(): void
    {
        $laura = $this->student('Laura Gómez', '1001234567');
        $loan = $this->lendTo($laura);
        $this->app->clock->advance('+20 days');

        $this->assertSame(LoanStatus::Overdue, $this->app->listLoans()->get($loan->id)->status);
        $this->assertThrows(StudentHasOverdue::class, fn () => $this->lendTo($laura));

        $this->app->returnItem()->returnItem($loan->id, $loan->items()[0]->id);

        $this->assertSame(LoanStatus::Open, $this->app->listLoans()->get($this->lendTo($laura)->id)->status);
    }

    public function test_loans_are_listed_newest_first_and_filtered_by_days_status_and_student(): void
    {
        $laura = $this->student('Laura Gómez', '1001234567');
        $pedro = $this->student('Pedro Ruiz', '1002223334');
        $first = $this->lendTo($laura);
        $this->app->returnItem()->returnItem($first->id, $first->items()[0]->id);
        $this->app->clock->advance('+1 day');
        $second = $this->lendTo($pedro);
        $this->app->clock->advance('+1 day');
        $third = $this->lendTo($laura);

        $list = $this->app->listLoans();
        $this->assertSame([$third->id, $second->id, $first->id], $this->ids($list->list(null, null, null, null, PageRequest::of())->items));
        $this->assertSame([$first->id], $this->ids($list->list(null, null, 'closed', null, PageRequest::of())->items));
        $this->assertSame([$third->id, $first->id], $this->ids($list->list(null, null, null, $laura->id, PageRequest::of())->items));
        $this->assertSame([$second->id], $this->ids($list->list('2026-11-02', '2026-11-02', null, null, PageRequest::of())->items));
        $this->assertSame([$third->id, $second->id], $this->ids($list->list('2026-11-02', null, '', '', PageRequest::of())->items));
        $this->assertSame(3, $list->list(null, null, null, null, PageRequest::of(1, 1))->total);
    }

    public function test_invalid_list_filters_are_rejected(): void
    {
        $list = $this->app->listLoans();

        $this->assertThrows(InvalidDateRange::class, fn () => $list->list('2026-11-30', '2026-11-01', null, null, PageRequest::of()));
        $this->assertThrows(ValidationFailed::class, fn () => $list->list('2026-02-30', null, null, null, PageRequest::of()));
        $this->assertThrows(ValidationFailed::class, fn () => $list->list(null, '30/11/2026', null, null, PageRequest::of()));
        $this->assertThrows(ValidationFailed::class, fn () => $list->list(null, null, 'lost', null, PageRequest::of()));
        $this->assertThrows(NotFound::class, fn () => $list->get('missing-loan'));
    }

    private function student(string $name, string $document): Student
    {
        return $this->app->createStudent()->create($name, $document);
    }

    private function lendTo(Student $student): Loan
    {
        return $this->app->lend()->lend($student->id, [$this->thinkPad->id], self::USER);
    }

    /** @param list<LoanView> $views */
    private function ids(array $views): array
    {
        return array_map(fn (LoanView $view) => $view->loan->id, $views);
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
