<?php

namespace App\Infrastructure\Persistence;

use App\Application\Ports\Page;
use App\Application\Ports\PageRequest;
use App\Application\Ports\StudentRepository;
use App\Domain\Exceptions\DocumentTaken;
use App\Domain\Student;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;

/** Students are inserted and read, never updated or deleted (CA-05.3). */
final readonly class DbStudentRepository implements StudentRepository
{
    public function __construct(private ConnectionInterface $connection)
    {
    }

    public function find(string $id): ?Student
    {
        if (! SqlValues::isUuid($id)) {
            return null;
        }

        $row = $this->table()->where('id', $id)->first(['id', 'full_name', 'document_number']);

        return $row === null ? null : self::toStudent($row);
    }

    public function documentExists(string $documentNumber): bool
    {
        return $this->table()->where('document_number', $documentNumber)->exists();
    }

    public function add(Student $student): void
    {
        try {
            // A savepoint keeps the caller's transaction usable after a unique violation.
            $this->connection->transaction(fn () => $this->table()->insert([
                'id' => $student->id,
                'full_name' => $student->fullName,
                'document_number' => $student->documentNumber->value,
            ]));
        } catch (QueryException $error) {
            if (SqlValues::isUniqueViolation($error)) {
                throw DocumentTaken::create($student->documentNumber->value);
            }
            throw $error;
        }
    }

    public function search(?string $text, PageRequest $page): Page
    {
        $query = $this->table()->select(['id', 'full_name', 'document_number']);

        if ($text !== null) {
            $pattern = SqlValues::containsPattern($text);
            $query->where(fn (Builder $match) => $match->where('full_name', 'ilike', $pattern)->orWhere('document_number', 'ilike', $pattern));
        }

        $total = (clone $query)->count();
        $rows = $query->orderByRaw('lower(full_name) COLLATE "C"')->orderBy('id')
            ->offset($page->offset())->limit($page->size)->get();

        return new Page($rows->map(self::toStudent(...))->all(), $page->page, $page->size, $total);
    }

    private function table(): Builder
    {
        return $this->connection->table('laptops.student');
    }

    private static function toStudent(object $row): Student
    {
        return Student::restore($row->id, $row->full_name, $row->document_number);
    }
}
