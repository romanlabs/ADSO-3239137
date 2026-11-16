<?php

namespace App\Infrastructure\Persistence;

use App\Application\Ports\LoanFilter;
use App\Application\Ports\LoanRepository;
use App\Application\Ports\OverdueEntry;
use App\Application\Ports\Page;
use App\Application\Ports\PageRequest;
use App\Application\Ports\StudentStanding;
use App\Domain\Exceptions\ItemAlreadyReturned;
use App\Domain\Laptop;
use App\Domain\Loan;
use App\Domain\LoanItem;
use App\Domain\LoanStatus;
use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use LogicException;

/**
 * Loans and their items. Units go through the laptop adapter of the same request, which holds the values read
 * before lending (ADR-002). The loan status is never stored: it is computed from the items at the given instant.
 */
final readonly class DbLoanRepository implements LoanRepository
{
    public function __construct(
        private ConnectionInterface $connection,
        private DbLaptopRepository $laptops,
    ) {
    }

    public function add(Loan $loan, array $withdrawnLaptops): void
    {
        // Units first: they are the contention point, and losing the race must leave nothing behind.
        $this->laptops->storeWithdrawnUnits($withdrawnLaptops);

        $this->connection->table('laptops.loan')->insert([
            'id' => $loan->id,
            'student_id' => $loan->studentId,
            'loaned_by_user_id' => $loan->loanedByUserId,
            'loaned_at' => SqlValues::instant($loan->loanedAt),
        ]);

        $this->connection->table('laptops.loan_item')->insert(array_map(fn (LoanItem $item) => [
            'id' => $item->id,
            'loan_id' => $loan->id,
            'laptop_id' => $item->laptopId,
            'laptop_name' => $item->laptopName,
            'model_code' => $item->modelCode,
            'category_name' => $item->categoryName,
            'due_at' => SqlValues::instant($item->dueAt),
            'returned_at' => null,
        ], $loan->items()));
    }

    public function find(string $id): ?Loan
    {
        if (! SqlValues::isUuid($id)) {
            return null;
        }

        $row = $this->connection->table('laptops.loan')->where('id', $id)->first();

        return $row === null ? null : $this->hydrate([$row])[0];
    }

    public function standingOf(string $studentId, DateTimeImmutable $now): StudentStanding
    {
        if (! SqlValues::isUuid($studentId)) {
            return new StudentStanding(0, 0);
        }

        $row = $this->connection->table('laptops.loan_item as i')
            ->join('laptops.loan as l', 'l.id', '=', 'i.loan_id')
            ->where('l.student_id', $studentId)
            ->whereNull('i.returned_at')
            ->selectRaw('count(*) AS unreturned, count(*) FILTER (WHERE i.due_at < ?) AS overdue', [SqlValues::instant($now)])
            ->first();

        return new StudentStanding((int) $row->unreturned, (int) $row->overdue);
    }

    public function saveReturn(Loan $loan, string $itemId, Laptop $laptop): void
    {
        $returnedAt = $loan->item($itemId)->returnedAt() ?? throw new LogicException('The item was not marked as returned.');

        $updated = $this->connection->table('laptops.loan_item')
            ->where('id', $itemId)
            ->where('loan_id', $loan->id)
            ->whereNull('returned_at')
            ->update(['returned_at' => SqlValues::instant($returnedAt)]);

        if ($updated === 0) {
            throw ItemAlreadyReturned::create();
        }

        $this->laptops->addOneUnit($laptop->id);
    }

    public function search(LoanFilter $filter, PageRequest $page, DateTimeImmutable $now): Page
    {
        if ($filter->studentId !== null && ! SqlValues::isUuid($filter->studentId)) {
            return new Page([], $page->page, $page->size, 0);
        }

        $query = $this->connection->table('laptops.loan as l');

        if ($filter->loanedFrom !== null) {
            $query->where('l.loaned_at', '>=', SqlValues::instant($filter->loanedFrom));
        }
        if ($filter->loanedBefore !== null) {
            $query->where('l.loaned_at', '<', SqlValues::instant($filter->loanedBefore));
        }
        if ($filter->studentId !== null) {
            $query->where('l.student_id', $filter->studentId);
        }
        if ($filter->status !== null) {
            $this->whereStatus($query, $filter->status, SqlValues::instant($now));
        }

        $total = (clone $query)->count();
        $rows = $query->select('l.*')->orderByDesc('l.loaned_at')->orderByDesc('l.id')
            ->offset($page->offset())->limit($page->size)->get()->all();

        return new Page($this->hydrate($rows), $page->page, $page->size, $total);
    }

    public function overdueItems(DateTimeImmutable $now): array
    {
        $rows = $this->connection->table('laptops.loan_item as i')
            ->join('laptops.loan as l', 'l.id', '=', 'i.loan_id')
            ->join('laptops.student as s', 's.id', '=', 'l.student_id')
            ->whereNull('i.returned_at')
            ->where('i.due_at', '<', SqlValues::instant($now))
            ->orderBy('i.due_at')->orderBy('i.id')
            ->select(['i.*', 's.full_name as student_name'])
            ->get();

        return $rows->map(fn (object $row) => new OverdueEntry($row->loan_id, $row->student_name, self::toItem($row)))->all();
    }

    /**
     * closed: nothing pending; overdue: something pending past its due instant; open: pending but nothing late.
     */
    private function whereStatus(Builder $query, LoanStatus $status, string $now): void
    {
        $pending = fn (Builder $items) => $items->from('laptops.loan_item as p')
            ->whereColumn('p.loan_id', 'l.id')->whereNull('p.returned_at');
        $late = fn (Builder $items) => $pending($items)->where('p.due_at', '<', $now);

        match ($status) {
            LoanStatus::Closed => $query->whereNotExists($pending),
            LoanStatus::Overdue => $query->whereExists($late),
            LoanStatus::Open => $query->whereExists($pending)->whereNotExists($late),
        };
    }

    /**
     * Loads the items of every given loan in one query (Q5) and keeps the order of the rows.
     *
     * @param  list<object>  $loanRows
     * @return list<Loan>
     */
    private function hydrate(array $loanRows): array
    {
        if ($loanRows === []) {
            return [];
        }

        $items = $this->connection->table('laptops.loan_item')
            ->whereIn('loan_id', array_map(fn (object $row) => $row->id, $loanRows))
            ->orderBy('id')
            ->get()
            ->groupBy('loan_id');

        return array_map(fn (object $row) => Loan::restore(
            $row->id,
            $row->student_id,
            $row->loaned_by_user_id,
            SqlValues::toInstant($row->loaned_at),
            ($items[$row->id] ?? collect())->map(self::toItem(...))->values()->all(),
        ), $loanRows);
    }

    private static function toItem(object $row): LoanItem
    {
        return new LoanItem(
            $row->id,
            $row->laptop_id,
            $row->laptop_name,
            $row->model_code,
            $row->category_name,
            SqlValues::toInstant($row->due_at),
            SqlValues::toInstant($row->returned_at),
        );
    }
}
