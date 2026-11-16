<?php

namespace Tests\Feature;

use App\Application\Ports\LoanRepository;
use App\Application\Ports\PageRequest;
use App\Application\Services\LoanView;
use App\Domain\Exceptions\ItemAlreadyReturned;
use App\Domain\Exceptions\StudentHasOverdue;
use App\Domain\Laptop;
use App\Domain\Loan;
use App\Domain\LoanStatus;
use App\Domain\Student;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Fakes\InMemoryCategoryRepository;
use Tests\Support\PostgresApplication;
use Tests\TestCase;

/**
 * T-13: the loan adapter computes the status from its items at the given instant (Q5 to Q8) and stores a return only
 * once. Driven through the services, as the HTTP layer will.
 */
final class LoanRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private PostgresApplication $system;

    private Laptop $laptop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->system = new PostgresApplication($this->app, '2026-10-20T09:00:00Z');
        $this->laptop = $this->system->createLaptop()->create('ThinkPad E14 Gen 5', 'Lenovo', 'TP-E14-G5', InMemoryCategoryRepository::PROGRAMMING, 5);
    }

    public function test_a_stored_loan_is_read_back_with_its_frozen_item(): void
    {
        $loan = $this->lend($this->student('Camila Rojas', '1001234567'));

        $read = $this->app->make(LoanRepository::class)->find($loan->id);
        $item = $read->items()[0];

        $this->assertEquals($loan->loanedAt, $read->loanedAt);
        $this->assertSame([$this->laptop->id, 'ThinkPad E14 Gen 5', 'TP-E14-G5', 'Programación'], [$item->laptopId, $item->laptopName, $item->modelCode, $item->categoryName]);
        $this->assertEquals(new DateTimeImmutable('2026-11-04T09:00:00Z'), $item->dueAt);
        $this->assertSame(4, DB::table('laptops.laptop')->where('id', $this->laptop->id)->value('available_units'));
        $this->assertNull($this->app->make(LoanRepository::class)->find('not-a-uuid'));
    }

    public function test_status_filter_is_computed_from_the_items_at_the_current_instant(): void
    {
        $late = $this->lend($this->student('Andrés Pérez', '1009876543'));
        $this->system->clock->advance('+10 days');
        $returned = $this->lend($this->student('Beatriz Gómez', 'CE778899'));
        $this->system->returnItem()->returnItem($returned->id, $returned->items()[0]->id);
        $this->system->clock->advance('+10 days');
        $open = $this->lend($this->student('Camila Rojas', '1001234567'));

        $this->assertSame([$open->id, $returned->id, $late->id], $this->ids(null));
        $this->assertSame([$open->id], $this->ids('open'));
        $this->assertSame([$late->id], $this->ids('overdue'));
        $this->assertSame([$returned->id], $this->ids('closed'));
    }

    public function test_date_range_and_student_filters_narrow_the_list(): void
    {
        $camila = $this->student('Camila Rojas', '1001234567');
        $first = $this->lend($camila);
        $this->system->returnItem()->returnItem($first->id, $first->items()[0]->id);
        $this->system->clock->advance('+3 days');
        $second = $this->lend($camila);
        $other = $this->lend($this->student('Andrés Pérez', '1009876543'));

        $byStudent = $this->system->listLoans()->list(null, null, null, $camila->id, PageRequest::of());
        $byDay = $this->system->listLoans()->list('2026-10-23', '2026-10-23', null, null, PageRequest::of());

        $this->assertSame([$second->id, $first->id], array_map(fn (LoanView $view) => $view->loan->id, $byStudent->items));
        $this->assertEqualsCanonicalizing([$second->id, $other->id], array_map(fn (LoanView $view) => $view->loan->id, $byDay->items));
        $this->assertSame(0, $this->system->listLoans()->list(null, null, null, 'nobody', PageRequest::of())->total);
    }

    public function test_overdue_items_carry_the_student_name_and_block_a_new_loan(): void
    {
        $student = $this->student('Andrés Pérez', '1009876543');
        $loan = $this->lend($student);
        $this->system->clock->advance('+20 days');

        $lines = $this->system->overdue()->list();

        $this->assertCount(1, $lines);
        $this->assertSame([$loan->id, 'Andrés Pérez', 'ThinkPad E14 Gen 5', 5], [$lines[0]->loanId, $lines[0]->studentName, $lines[0]->laptopName, $lines[0]->daysLate]);

        $this->expectException(StudentHasOverdue::class);
        $this->lend($student);
    }

    public function test_returning_twice_is_a_conflict_and_adds_one_unit(): void
    {
        $loan = $this->lend($this->student('Camila Rojas', '1001234567'));
        $itemId = $loan->items()[0]->id;

        $this->system->returnItem()->returnItem($loan->id, $itemId);

        try {
            $this->system->returnItem()->returnItem($loan->id, $itemId);
            $this->fail('Expected ItemAlreadyReturned.');
        } catch (ItemAlreadyReturned) {
            $this->assertSame(5, DB::table('laptops.laptop')->where('id', $this->laptop->id)->value('available_units'));
        }

        $this->assertSame(LoanStatus::Closed, $this->system->listLoans()->get($loan->id)->status);
    }

    private function student(string $name, string $document): Student
    {
        return $this->system->createStudent()->create($name, $document);
    }

    private function lend(Student $student): Loan
    {
        return $this->system->lend()->lend($student->id, [$this->laptop->id], $this->system->operatorId);
    }

    /** @return list<string> */
    private function ids(?string $status): array
    {
        $page = $this->system->listLoans()->list(null, null, $status, null, PageRequest::of());

        return array_map(fn (LoanView $view) => $view->loan->id, $page->items);
    }
}
