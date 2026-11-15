<?php

namespace Tests\Unit\Application;

use App\Application\Ports\PageRequest;
use App\Application\Services\SearchStudentsService;
use App\Domain\Exceptions\DocumentTaken;
use App\Domain\Exceptions\DomainError;
use App\Domain\Exceptions\DuplicateLaptopInLoan;
use App\Domain\Exceptions\LaptopNotAvailable;
use App\Domain\Exceptions\NotFound;
use App\Domain\Exceptions\StudentHasOverdue;
use App\Domain\Exceptions\StudentLimitExceeded;
use App\Domain\Exceptions\ValidationFailed;
use App\Domain\Laptop;
use App\Domain\LoanStatus;
use App\Domain\Student;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryApplication;
use Tests\Fakes\InMemoryCategoryRepository;

final class LendLaptopServiceTest extends TestCase
{
    private const USER = 'assistant-user-id';

    private InMemoryApplication $app;

    private Student $laura;

    private Laptop $thinkPad;

    private Laptop $macBook;

    protected function setUp(): void
    {
        $this->app = new InMemoryApplication('2026-11-16T14:15:00Z');
        $this->laura = $this->app->createStudent()->create('Laura  Gómez ', '1001234567');
        $this->thinkPad = $this->app->createLaptop()->create('ThinkPad E14 Gen 5', 'Lenovo', 'TP-E14-G5', InMemoryCategoryRepository::PROGRAMMING, 2);
        $this->macBook = $this->app->createLaptop()->create('MacBook Air M2', 'Apple', 'MBA-M2-13', InMemoryCategoryRepository::DESIGN, 1);
    }

    public function test_lending_withdraws_one_unit_freezes_the_catalog_and_is_due_in_fifteen_days(): void
    {
        $loan = $this->app->lend()->lend($this->laura->id, [$this->thinkPad->id], self::USER);

        $stored = $this->app->loans->find($loan->id);
        $item = $stored->items()[0];
        $this->assertSame($this->laura->id, $stored->studentId);
        $this->assertSame(self::USER, $stored->loanedByUserId);
        $this->assertSame('2026-11-16T14:15:00+00:00', $stored->loanedAt->format(DATE_ATOM));
        $this->assertSame('2026-12-01T14:15:00+00:00', $item->dueAt->format(DATE_ATOM));
        $this->assertSame(['ThinkPad E14 Gen 5', 'TP-E14-G5', 'Programación'], [$item->laptopName, $item->modelCode, $item->categoryName]);
        $this->assertSame(LoanStatus::Open, $stored->status($this->app->clock->now()));
        $this->assertSame(1, $this->app->laptops->stored($this->thinkPad->id)->availableUnits());
        $this->assertSame(1, $this->app->transactions->runs);
    }

    public function test_frozen_values_survive_a_later_rename_of_the_laptop(): void
    {
        $loan = $this->app->lend()->lend($this->laura->id, [$this->thinkPad->id], self::USER);

        $this->app->updateLaptop()->update($this->thinkPad->id, 'ThinkPad E14 (renombrado)', 'Lenovo', InMemoryCategoryRepository::BASIC, 2);

        $item = $this->app->loans->find($loan->id)->items()[0];
        $this->assertSame('ThinkPad E14 Gen 5', $item->laptopName);
        $this->assertSame('Programación', $item->categoryName);
    }

    public function test_a_loan_needs_at_least_one_laptop(): void
    {
        $this->assertLendFails(ValidationFailed::class, $this->laura->id, []);
    }

    public function test_repeated_laptop_is_reported_before_anything_else(): void
    {
        $error = $this->assertLendFails(DuplicateLaptopInLoan::class, 'missing-student', ['missing-laptop', 'missing-laptop']);

        $this->assertSame('items[1].laptop_id', $error->details()[0]['field']);
    }

    public function test_missing_student_or_laptop_is_not_found_even_if_the_student_has_overdue_items(): void
    {
        $this->assertLendFails(NotFound::class, 'missing-student', [$this->thinkPad->id]);

        $this->makeLauraOverdue();
        $this->assertLendFails(NotFound::class, $this->laura->id, ['missing-laptop']);
    }

    public function test_retired_laptop_cannot_be_lent(): void
    {
        $retired = $this->app->laptops->find($this->macBook->id);
        $retired->retire($this->app->clock->now());
        $this->app->laptops->save($retired);

        $this->assertLendFails(NotFound::class, $this->laura->id, [$this->macBook->id]);
    }

    public function test_student_with_an_overdue_laptop_gets_overdue_error_before_limit_error(): void
    {
        $this->makeLauraOverdue();

        $this->assertLendFails(StudentHasOverdue::class, $this->laura->id, [$this->macBook->id]);
    }

    public function test_one_unreturned_laptop_is_the_limit_and_the_error_carries_the_current_count(): void
    {
        $this->app->lend()->lend($this->laura->id, [$this->thinkPad->id], self::USER);

        $error = $this->assertLendFails(StudentLimitExceeded::class, $this->laura->id, [$this->macBook->id]);

        $this->assertSame(['field' => 'student_id', 'issue' => 'sin devolver: 1'], $error->details()[0]);
    }

    public function test_asking_for_two_laptops_at_once_exceeds_the_limit(): void
    {
        $this->assertLendFails(StudentLimitExceeded::class, $this->laura->id, [$this->thinkPad->id, $this->macBook->id]);
    }

    public function test_limit_is_reported_before_missing_units(): void
    {
        $pedro = $this->app->createStudent()->create('Pedro Ruiz', '1002223334');
        $this->app->lend()->lend($pedro->id, [$this->macBook->id], self::USER);
        $this->app->lend()->lend($this->laura->id, [$this->thinkPad->id], self::USER);

        $this->assertLendFails(StudentLimitExceeded::class, $this->laura->id, [$this->macBook->id]);
    }

    public function test_no_units_left_is_a_conflict_and_nothing_is_stored(): void
    {
        $pedro = $this->app->createStudent()->create('Pedro Ruiz', '1002223334');
        $this->app->lend()->lend($pedro->id, [$this->macBook->id], self::USER);

        $error = $this->assertLendFails(LaptopNotAvailable::class, $this->laura->id, [$this->macBook->id]);

        $this->assertSame('items[0].laptop_id', $error->details()[0]['field']);
        $this->assertCount(1, $this->app->loans->all());
        $this->assertSame(0, $this->app->loans->standingOf($this->laura->id, $this->app->clock->now())->unreturned);
    }

    public function test_losing_the_race_for_the_last_unit_stores_no_loan(): void
    {
        $this->app->laptops->anotherOperatorTakesAUnitAfterNextRead($this->macBook->id);

        $this->assertLendFails(LaptopNotAvailable::class, $this->laura->id, [$this->macBook->id]);

        $this->assertSame([], $this->app->loans->all());
        $this->assertSame(0, $this->app->laptops->stored($this->macBook->id)->availableUnits());
    }

    public function test_student_detail_counts_unreturned_items_and_search_finds_by_name_or_document(): void
    {
        $search = new SearchStudentsService($this->app->students, $this->app->loans, $this->app->clock);
        $this->app->createStudent()->create('Andrés Pérez', '2003004005');
        $this->app->lend()->lend($this->laura->id, [$this->thinkPad->id], self::USER);

        $detail = $search->get($this->laura->id);
        $this->assertSame('Laura Gómez', $detail->student->fullName);
        $this->assertSame(1, $detail->activeLoans);

        $this->assertSame(['Andrés Pérez', 'Laura Gómez'], array_map(fn (Student $s) => $s->fullName, $search->search(null, PageRequest::of())->items));
        $this->assertSame(1, $search->search('gómez', PageRequest::of())->total);
        $this->assertSame(1, $search->search('200300', PageRequest::of())->total);
        $this->assertThrows(NotFound::class, fn () => $search->get('missing-student'));
    }

    public function test_document_number_is_unique(): void
    {
        $this->assertThrows(DocumentTaken::class, fn () => $this->app->createStudent()->create('Otra Persona', ' 1001234567 '));
        $this->assertThrows(ValidationFailed::class, fn () => $this->app->createStudent()->create('Sin Documento', '12'));
    }

    private function makeLauraOverdue(): void
    {
        $this->app->lend()->lend($this->laura->id, [$this->thinkPad->id], self::USER);
        $this->app->clock->advance('+16 days');
    }

    /** @param list<string> $laptopIds */
    private function assertLendFails(string $expected, string $studentId, array $laptopIds): DomainError
    {
        $loansBefore = count($this->app->loans->all());

        try {
            $this->app->lend()->lend($studentId, $laptopIds, self::USER);
        } catch (DomainError $error) {
            $this->assertInstanceOf($expected, $error);
            $this->assertCount($loansBefore, $this->app->loans->all());

            return $error;
        }

        $this->fail("Expected {$expected} to be thrown.");
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
