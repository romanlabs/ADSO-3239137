<?php

namespace Tests\Unit\Domain;

use App\Domain\Category;
use App\Domain\Exceptions\DuplicateLaptopInLoan;
use App\Domain\Exceptions\ItemAlreadyReturned;
use App\Domain\Exceptions\LaptopNotAvailable;
use App\Domain\Exceptions\StudentHasOverdue;
use App\Domain\Exceptions\StudentLimitExceeded;
use App\Domain\Laptop;
use App\Domain\Loan;
use App\Domain\LoanStatus;
use App\Domain\ModelCode;
use App\Domain\Quantity;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class LoanTest extends TestCase
{
    private DateTimeImmutable $lentAt;

    protected function setUp(): void
    {
        $this->lentAt = new DateTimeImmutable('2026-11-16T14:15:00Z');
    }

    private function laptop(string $id = 'laptop-1', int $units = 2): Laptop
    {
        return Laptop::register($id, 'MacBook Air M2', 'Apple', ModelCode::from('MBA-M2-13'), new Category('cat', 'Diseño'), Quantity::from($units));
    }

    public function test_item_is_due_fifteen_days_after_lending_and_freezes_catalog_values(): void
    {
        $laptop = $this->laptop();
        $loan = Loan::open('loan-1', 'student-1', 'user-1', $this->lentAt);

        $item = $loan->addItem('item-1', $laptop);

        $this->assertSame('2026-12-01T14:15:00+00:00', $item->dueAt->format(DATE_ATOM));
        $this->assertSame('MacBook Air M2', $item->laptopName);
        $this->assertSame('MBA-M2-13', $item->modelCode);
        $this->assertSame('Diseño', $item->categoryName);
        $this->assertSame(1, $laptop->availableUnits());
    }

    public function test_frozen_values_do_not_follow_later_catalog_changes(): void
    {
        $laptop = $this->laptop();
        $loan = Loan::open('loan-1', 'student-1', 'user-1', $this->lentAt);
        $item = $loan->addItem('item-1', $laptop);

        $laptop->update('MacBook Air 13 (2022)', 'Apple', new Category('cat2', 'Básicos'), Quantity::from(2));

        $this->assertSame('MacBook Air M2', $item->laptopName);
        $this->assertSame('Diseño', $item->categoryName);
    }

    public function test_status_moves_from_open_to_overdue_to_closed(): void
    {
        $laptop = $this->laptop();
        $loan = Loan::open('loan-1', 'student-1', 'user-1', $this->lentAt);
        $loan->addItem('item-1', $laptop);

        $this->assertSame(LoanStatus::Open, $loan->status(new DateTimeImmutable('2026-12-01T14:15:00Z')));
        $this->assertSame(LoanStatus::Overdue, $loan->status(new DateTimeImmutable('2026-12-01T14:15:01Z')));
        $this->assertSame(3, $loan->item('item-1')->daysLate(new DateTimeImmutable('2026-12-04T15:00:00Z')));

        $loan->returnItem('item-1', $laptop, new DateTimeImmutable('2026-12-04T15:00:00Z'));

        $this->assertSame(LoanStatus::Closed, $loan->status(new DateTimeImmutable('2026-12-04T15:00:00Z')));
        $this->assertSame(2, $laptop->availableUnits());
    }

    public function test_returning_twice_never_restocks_twice(): void
    {
        $laptop = $this->laptop();
        $loan = Loan::open('loan-1', 'student-1', 'user-1', $this->lentAt);
        $loan->addItem('item-1', $laptop);
        $loan->returnItem('item-1', $laptop, $this->lentAt);

        try {
            $loan->returnItem('item-1', $laptop, $this->lentAt);
            $this->fail('A second return must be rejected.');
        } catch (ItemAlreadyReturned) {
            $this->assertSame(2, $laptop->availableUnits());
        }
    }

    public function test_a_loan_holds_at_most_one_laptop(): void
    {
        $loan = Loan::open('loan-1', 'student-1', 'user-1', $this->lentAt);
        $loan->addItem('item-1', $this->laptop('laptop-1'));

        $this->expectException(StudentLimitExceeded::class);
        $loan->addItem('item-2', $this->laptop('laptop-2'));
    }

    public function test_lending_without_units_registers_nothing(): void
    {
        $laptop = $this->laptop('laptop-1', 1);
        $laptop->withdraw();
        $loan = Loan::open('loan-1', 'student-1', 'user-1', $this->lentAt);

        try {
            $loan->addItem('item-1', $laptop);
            $this->fail('Lending without units must be rejected.');
        } catch (LaptopNotAvailable) {
            $this->assertSame([], $loan->items());
        }
    }

    public function test_student_with_an_unreturned_laptop_cannot_borrow_another(): void
    {
        $this->expectException(StudentLimitExceeded::class);
        Loan::ensureStudentMayBorrow(1, false, ['laptop-1']);
    }

    public function test_student_with_overdue_laptop_cannot_borrow(): void
    {
        $this->expectException(StudentHasOverdue::class);
        Loan::ensureStudentMayBorrow(1, true, ['laptop-1']);
    }

    public function test_the_same_laptop_twice_in_one_request_is_rejected_first(): void
    {
        $this->expectException(DuplicateLaptopInLoan::class);
        Loan::ensureStudentMayBorrow(0, true, ['laptop-1', 'laptop-1']);
    }

    public function test_student_without_pending_loans_may_borrow_one(): void
    {
        Loan::ensureStudentMayBorrow(0, false, ['laptop-1']);
        $this->addToAssertionCount(1);
    }
}
