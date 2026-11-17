<?php

namespace App\Infrastructure\Http;

use App\Application\Ports\MostRequestedRow;
use App\Application\Ports\Page;
use App\Application\Services\LoanView;
use App\Application\Services\MostRequestedReport;
use App\Application\Services\OverdueLine;
use App\Application\Services\StudentDetail;
use App\Domain\Category;
use App\Domain\Laptop;
use App\Domain\LoanItem;
use App\Domain\Student;
use App\Domain\User;
use DateTimeImmutable;
use DateTimeZone;

/**
 * JSON shapes of the contract (§3). Field names are snake_case; instants are ISO 8601 in UTC with `Z`.
 * Nothing here ever reads a password or a password hash.
 */
final class Presenters
{
    public static function instant(?DateTimeImmutable $instant): ?string
    {
        return $instant?->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * @template T
     *
     * @param  Page<T>  $page
     * @param  callable(T): array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public static function page(Page $page, callable $item): array
    {
        return [
            'items' => array_map($item, $page->items),
            'page' => $page->page,
            'size' => $page->size,
            'total' => $page->total,
        ];
    }

    /** @return array<string, string> */
    public static function user(User $user): array
    {
        return ['id' => $user->id, 'username' => $user->username, 'role' => $user->role->value];
    }

    /** @return array<string, string> */
    public static function category(Category $category): array
    {
        return ['id' => $category->id, 'name' => $category->name];
    }

    /** @return array<string, mixed> */
    public static function laptop(Laptop $laptop): array
    {
        return [
            'id' => $laptop->id,
            'name' => $laptop->name(),
            'brand' => $laptop->brand(),
            'model_code' => $laptop->modelCode->value,
            'category' => self::category($laptop->category()),
            'total_units' => $laptop->totalUnits(),
            'available_units' => $laptop->availableUnits(),
            'photo_key' => $laptop->photoKey(),
        ];
    }

    /** @return array<string, string> */
    public static function student(Student $student): array
    {
        return ['id' => $student->id, 'full_name' => $student->fullName, 'document_number' => $student->documentNumber->value];
    }

    /** @return array<string, mixed> */
    public static function studentDetail(StudentDetail $detail): array
    {
        return self::student($detail->student) + ['active_loans' => $detail->activeLoans];
    }

    /** @return array<string, mixed> */
    public static function loan(LoanView $view): array
    {
        return [
            'id' => $view->loan->id,
            'student_id' => $view->loan->studentId,
            'loaned_at' => self::instant($view->loan->loanedAt),
            'status' => $view->status->value,
            'items' => array_map(self::item(...), $view->loan->items()),
        ];
    }

    /** @return array<string, mixed> */
    public static function item(LoanItem $item): array
    {
        return [
            'id' => $item->id,
            'laptop_id' => $item->laptopId,
            'laptop_name' => $item->laptopName,
            'model_code' => $item->modelCode,
            'category_name' => $item->categoryName,
            'due_at' => self::instant($item->dueAt),
            'returned_at' => self::instant($item->returnedAt()),
        ];
    }

    /** @return array<string, mixed> */
    public static function overdue(OverdueLine $line): array
    {
        return [
            'loan_id' => $line->loanId,
            'item_id' => $line->itemId,
            'laptop_name' => $line->laptopName,
            'student_name' => $line->studentName,
            'due_at' => self::instant($line->dueAt),
            'days_late' => $line->daysLate,
        ];
    }

    /** CA-10.3: rows carry no student or assistant data. @return array<string, mixed> */
    public static function report(MostRequestedReport $report): array
    {
        return [
            'from' => $report->range->from->format('Y-m-d'),
            'to' => $report->range->to->format('Y-m-d'),
            'rows' => array_map(fn (MostRequestedRow $row) => [
                'laptop_id' => $row->laptopId,
                'laptop_name' => $row->laptopName,
                'category_name' => $row->categoryName,
                'times_requested' => $row->timesRequested,
            ], $report->rows),
        ];
    }
}
